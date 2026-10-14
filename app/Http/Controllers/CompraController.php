<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Exists;

class CompraController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $compras= Compra::with(['user','proveedor','almacen_producto_compra'])->orderBy('id')->paginate(10);

        return response()->Json($compras);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            //"codigo"=>"required",
            //"fecha"=>"required",
            "proveedor_id"=>"required|exists:proveedors,id",
            //"user_id"=>"required",
            "descuento_total"=>"nullable",
            "detalle"=>"nullable",
            "observaciones"=>"nullable",
            "movimientos.*.almacen_id"=>"required|exists:almacens,id",
            "movimientos.*.producto_id"=>"required|exists:productos,id",
            "movimientos.*.cantidad"=>"required|integer|min:1",
            "movimientos.*.precio_unitario_compra"=>"required|numeric"
        ]);

        try {
             //transacciones
         DB::beginTransaction();
        //verificacion si existe el proveedor en la BD
           //en store ya se valida
        //Creacion de una nota de compra en la BD
        $compra=new Compra();
        $compra->codigo="";
        $compra->fecha=date('Y-m-d H:i:s');
        $compra->detalle=$request->detalle;
        $compra->observaciones=$request->observaciones;
        $compra->user_id=$request->user()->id;
        $compra->proveedor_id=$request->proveedor_id;
        $compra->estado="En proceso";
        $compra->save();

        $compra->codigo='COMP-'.str_pad($compra->id,6,'0',STR_PAD_LEFT);
        
        //registrar movimientos en la BD
        //actualizacion de producto por almacen
        foreach($request->movimientos as $mov){
            $compra->almacen_producto_compra()->attach($mov['producto_id'], [
                'almacen_id'=>$mov['almacen_id'],
                'cantidad'=>$mov['cantidad'],
                'precio_unitario_compra'=>$mov['precio_unitario_compra'],
                'observaciones'=>$mov['observaciones']
            ]);
        //actualizamos el stock
        $pivot=DB::table('almacen_producto')
                    ->where('almacen_id',$mov['almacen_id'])
                    ->where('producto_id',$mov['producto_id'])
                    ->first();

        if(!$pivot){
            DB::table('almacen_producto')->insert([
                "almacen_id"=>$mov['almacen_id'],
                "producto_id"=>$mov['producto_id'],
                "cantidad_actual"=>$mov['cantidad'],
                "fecha_actualizacion"=>date('Y-m-d H:i:s')
            ]);
        }else{
            $nuevaCantidad=$pivot->cantidad_actual;

            $nuevaCantidad=$nuevaCantidad+$mov['cantidad'];
            DB::table('almacen_producto')
                    ->where('almacen_id',$mov['almacen_id'])
                    ->where('producto_id',$mov['producto_id'])
                    ->update([
                        "cantidad_actual"=>$nuevaCantidad,
                        "fecha_actualizacion"=>date('Y-m-d H:i:s')

                    ]);
        }

        }



        //Verificar: almacen_id y producto_id existe en la BD
          //ya esta en store validate

        //Registra todo en la BD
        //Registrar nota de compra
        $compra->estado="COMPLETADO";
        $compra->update();

        DB::commit();
        return response()->json(["mensaje"=>"nota creada correctamente"],201);
       
        //todo bien

        } catch(\Exception $e) {
            //revierte todo
            DB::rollBack();
         
            return response()->json(["mensaje"=>"Error al registrar la nota de compra", "error"=>$e->getMessage()],500);

        }

       
       

        return response()->json(["mensaje"=>"enproceso de compra"]);

        //transacciones

        //Registrar nota de compra





    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
