<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class VentaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ventas = Venta::with(['user', 'cliente', 'almacen_producto_venta'])->orderBy('id')->paginate(10);

        return response()->json($ventas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'descuento_total' => 'nullable|numeric',
            'detalle' => 'nullable',
            'observaciones' => 'nullable',
            'movimientos' => 'required|array|min:1',
            'movimientos.*.almacen_id' => 'required|exists:almacens,id',
            'movimientos.*.producto_id' => 'required|exists:productos,id',
            'movimientos.*.cantidad' => 'required|integer|min:1',
            'movimientos.*.precio_unitario_venta' => 'required|numeric',
        ]);

        try {
            // transacciones
            DB::beginTransaction();

            // Creacion de una nota de venta en la BD
            $venta = new Venta;
            $venta->codigo = '';
            $venta->fecha = date('Y-m-d H:i:s');
            $venta->detalle = $request->detalle;
            $venta->descuento_total = $request->descuento_total ?? 0;
            $venta->observaciones = $request->observaciones;
            $venta->user_id = $request->user()->id;
            $venta->cliente_id = $request->cliente_id;
            $venta->estado = 'En proceso';
            $venta->save();

            $venta->codigo = 'VENT-'.str_pad($venta->id, 6, '0', STR_PAD_LEFT);

            // registrar movimientos en la BD
            // actualizacion de stock por almacen (la venta descuenta)
            foreach ($request->movimientos as $mov) {
                // verificamos el stock disponible en el almacen
                $pivot = DB::table('almacen_producto')
                    ->where('almacen_id', $mov['almacen_id'])
                    ->where('producto_id', $mov['producto_id'])
                    ->first();

                if (! $pivot || $pivot->cantidad_actual < $mov['cantidad']) {
                    throw new \Exception("Stock insuficiente para el producto {$mov['producto_id']} en el almacen {$mov['almacen_id']}");
                }

                $venta->almacen_producto_venta()->attach($mov['producto_id'], [
                    'almacen_id' => $mov['almacen_id'],
                    'cantidad' => $mov['cantidad'],
                    'precio_unitario_venta' => $mov['precio_unitario_venta'],
                    'observaciones' => $mov['observaciones'] ?? null,
                ]);

                DB::table('almacen_producto')
                    ->where('almacen_id', $mov['almacen_id'])
                    ->where('producto_id', $mov['producto_id'])
                    ->update([
                        'cantidad_actual' => $pivot->cantidad_actual - $mov['cantidad'],
                        'fecha_actualizacion' => date('Y-m-d H:i:s'),
                    ]);
            }

            // Registrar nota de venta
            $venta->estado = 'COMPLETADO';
            $venta->update();

            DB::commit();

            return response()->json(['mensaje' => 'nota de venta creada correctamente'], 201);

        } catch (\Exception $e) {
            // revierte todo
            DB::rollBack();

            return response()->json(['mensaje' => 'Error al registrar la nota de venta', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $venta = Venta::with(['user', 'cliente', 'almacen_producto_venta'])->find($id);

        return response()->json($venta);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // solo se actualizan los datos de cabecera (no los movimientos ni el stock)
        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'descuento_total' => 'nullable|numeric',
            'detalle' => 'nullable',
            'observaciones' => 'nullable',
            'estado' => 'nullable',
        ]);

        $venta = Venta::find($id);
        $venta->cliente_id = $request->cliente_id;
        $venta->descuento_total = $request->descuento_total ?? $venta->descuento_total;
        $venta->detalle = $request->detalle;
        $venta->observaciones = $request->observaciones;
        $venta->estado = $request->estado ?? $venta->estado;
        $venta->update();

        return response()->json(['mensaje' => 'venta actualizada']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            DB::beginTransaction();

            $venta = Venta::with('almacen_producto_venta')->find($id);

            // devolvemos el stock de cada producto antes de eliminar la venta
            foreach ($venta->almacen_producto_venta as $producto) {
                DB::table('almacen_producto')
                    ->where('almacen_id', $producto->pivot->almacen_id)
                    ->where('producto_id', $producto->id)
                    ->update([
                        'cantidad_actual' => DB::raw("cantidad_actual + {$producto->pivot->cantidad}"),
                        'fecha_actualizacion' => date('Y-m-d H:i:s'),
                    ]);
            }

            // eliminamos el detalle y luego la venta
            DB::table('detalle_venta')->where('venta_id', $venta->id)->delete();
            $venta->delete();

            DB::commit();

            return response()->json(['mensaje' => 'venta eliminada']);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['mensaje' => 'Error al eliminar la venta', 'error' => $e->getMessage()], 500);
        }
    }

    public function generarReciboPDF($id){
        $data = Venta::with(['user','cliente','almacen_producto_venta'])->find($id);

        if (!$data) {
            return response()->json(['mensaje' => 'Venta no encontrada'], 404);
        }

        // Calcular subtotal
        $subtotal = 0;
        foreach ($data->almacen_producto_venta as $producto) {
            $cantidad = (float) ($producto->pivot->cantidad ?? 0);
            $precio = (float) ($producto->pivot->precio_unitario_venta ?? 0);
            $subtotal += ($cantidad * $precio);
        }

        $descuento = (float) ($data->descuento_total ?? 0);
        $total = $subtotal - $descuento;
        if ($total < 0) {
            $total = 0;
        }

        $pdf = Pdf::loadView('pdf.recibo', [
            'data' => $data,
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'total' => $total,
        ]);
        return $pdf->download("$data->codigo-recibo.pdf");
    }


}
