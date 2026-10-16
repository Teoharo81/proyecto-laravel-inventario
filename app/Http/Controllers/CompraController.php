<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class CompraController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $compras = Compra::with(['user', 'proveedor', 'almacen_producto_compra'])->orderBy('id')->paginate(10);

        return response()->json($compras);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'proveedor_id' => 'required|exists:proveedors,id',
            'descuento_total' => 'nullable|numeric',
            'detalle' => 'nullable',
            'observaciones' => 'nullable',
            'movimientos' => 'required|array|min:1',
            'movimientos.*.almacen_id' => 'required|exists:almacens,id',
            'movimientos.*.producto_id' => 'required|exists:productos,id',
            'movimientos.*.cantidad' => 'required|integer|min:1',
            'movimientos.*.precio_unitario_compra' => 'required|numeric',
        ]);

        try {
            // transacciones
            DB::beginTransaction();

            // Creacion de una nota de compra en la BD
            $compra = new Compra;
            $compra->codigo = '';
            $compra->fecha = date('Y-m-d H:i:s');
            $compra->detalle = $request->detalle;
            $compra->descuento_total = $request->descuento_total ?? 0;
            $compra->observaciones = $request->observaciones;
            $compra->user_id = $request->user()->id;
            $compra->proveedor_id = $request->proveedor_id;
            $compra->estado = 'En proceso';
            $compra->save();

            $compra->codigo = 'COMP-'.str_pad($compra->id, 6, '0', STR_PAD_LEFT);

            // registrar movimientos en la BD
            // actualizacion de producto por almacen
            foreach ($request->movimientos as $mov) {
                $compra->almacen_producto_compra()->attach($mov['producto_id'], [
                    'almacen_id' => $mov['almacen_id'],
                    'cantidad' => $mov['cantidad'],
                    'precio_unitario_compra' => $mov['precio_unitario_compra'],
                    'observaciones' => $mov['observaciones'] ?? null,
                ]);

                // actualizamos el stock
                $pivot = DB::table('almacen_producto')
                    ->where('almacen_id', $mov['almacen_id'])
                    ->where('producto_id', $mov['producto_id'])
                    ->first();

                if (! $pivot) {
                    DB::table('almacen_producto')->insert([
                        'almacen_id' => $mov['almacen_id'],
                        'producto_id' => $mov['producto_id'],
                        'cantidad_actual' => $mov['cantidad'],
                        'fecha_actualizacion' => date('Y-m-d H:i:s'),
                    ]);
                } else {
                    DB::table('almacen_producto')
                        ->where('almacen_id', $mov['almacen_id'])
                        ->where('producto_id', $mov['producto_id'])
                        ->update([
                            'cantidad_actual' => $pivot->cantidad_actual + $mov['cantidad'],
                            'fecha_actualizacion' => date('Y-m-d H:i:s'),
                        ]);
                }
            }

            // Registrar nota de compra
            $compra->estado = 'COMPLETADO';
            $compra->update();

            DB::commit();

            return response()->json(['mensaje' => 'nota de compra creada correctamente'], 201);

        } catch (\Exception $e) {
            // revierte todo
            DB::rollBack();

            return response()->json(['mensaje' => 'Error al registrar la nota de compra', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $compra = Compra::with(['user', 'proveedor', 'almacen_producto_compra'])->find($id);

        if (! $compra) {
            return response()->json(['mensaje' => 'Compra no encontrada'], 404);
        }

        return response()->json($compra);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // solo se actualizan los datos de cabecera (no los movimientos ni el stock)
        $request->validate([
            'proveedor_id' => 'required|exists:proveedors,id',
            'descuento_total' => 'nullable|numeric',
            'detalle' => 'nullable',
            'observaciones' => 'nullable',
            'estado' => 'nullable',
        ]);

        $compra = Compra::find($id);

        if (! $compra) {
            return response()->json(['mensaje' => 'Compra no encontrada'], 404);
        }

        $compra->proveedor_id = $request->proveedor_id;
        $compra->descuento_total = $request->descuento_total ?? $compra->descuento_total;
        $compra->detalle = $request->detalle;
        $compra->observaciones = $request->observaciones;
        $compra->estado = $request->estado ?? $compra->estado;
        $compra->update();

        return response()->json(['mensaje' => 'compra actualizada']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            DB::beginTransaction();

            $compra = Compra::with('almacen_producto_compra')->find($id);

            if (! $compra) {
                return response()->json(['mensaje' => 'Compra no encontrada'], 404);
            }

            // la compra sumó stock al registrarse, por lo que al eliminarla se descuenta
            foreach ($compra->almacen_producto_compra as $producto) {
                $pivot = DB::table('almacen_producto')
                    ->where('almacen_id', $producto->pivot->almacen_id)
                    ->where('producto_id', $producto->id)
                    ->first();

                if (! $pivot || $pivot->cantidad_actual < $producto->pivot->cantidad) {
                    throw new \Exception("Stock insuficiente para revertir el producto {$producto->id} en el almacen {$producto->pivot->almacen_id}");
                }

                DB::table('almacen_producto')
                    ->where('almacen_id', $producto->pivot->almacen_id)
                    ->where('producto_id', $producto->id)
                    ->update([
                        'cantidad_actual' => DB::raw("cantidad_actual - {$producto->pivot->cantidad}"),
                        'fecha_actualizacion' => date('Y-m-d H:i:s'),
                    ]);
            }

            // eliminamos el detalle y luego la compra
            DB::table('detalle_compra')->where('compra_id', $compra->id)->delete();
            $compra->delete();

            DB::commit();

            return response()->json(['mensaje' => 'compra eliminada']);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['mensaje' => 'Error al eliminar la compra', 'error' => $e->getMessage()], 500);
        }
    }

    public function generarReciboPDF($id)
    {
        $data = Compra::with(['user', 'proveedor', 'almacen_producto_compra'])->find($id);

        if (! $data) {
            return response()->json(['mensaje' => 'Compra no encontrada'], 404);
        }

        // Calcular subtotal
        $subtotal = 0;
        foreach ($data->almacen_producto_compra as $producto) {
            $cantidad = (float) ($producto->pivot->cantidad ?? 0);
            $precio = (float) ($producto->pivot->precio_unitario_compra ?? 0);
            $subtotal += ($cantidad * $precio);
        }

        $descuento = (float) ($data->descuento_total ?? 0);
        $total = $subtotal - $descuento;
        if ($total < 0) {
            $total = 0;
        }

        $pdf = Pdf::loadView('pdf.recibo_compra', [
            'data' => $data,
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'total' => $total,
        ]);

        return $pdf->download("$data->codigo-recibo.pdf");
    }
}
