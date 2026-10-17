<?php

namespace App\Http\Controllers;

use App\Exports\ProductosExport;
use App\Models\Producto;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limite=$request->limit;//para limites 
        $buscar=$request->search;//para buscar
        $almacen=$request->almacen_id;
        //listado de productos

        $productos=Producto::with(["categoria",'almacenes'])//traer la categoria del producto
                ->where(function($query) use ($buscar){//agrupar los filtros de busqueda
                    $query->where("nombre","iLIKE","%$buscar%")//buscar por nombre
                        ->orwhere("codigo_barra","iLIKE","%$buscar%");//buscar por codigo_barra
                })
                ->orderby('id','DESC');//ordenar descendente
        
        if(isset($almacen)){
            $productos=$productos->whereHas("almacenes",function($query)use ($almacen){
                $query->where("almacens.id","=",$almacen);
            });

        }
        
        $productos=$productos->paginate($limite);//paginacion, 10 item por hoja
        return response()->json($productos);
        
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //validar
        $request->validate([
            "nombre"=>"required",
            "descripcion"=>"nullable",
            "codigo_barra"=>"nullable",
            "unidad_medida"=>"nullable",
            "marca"=>"nullable",
            "categoria_id"=>"required|exists:categorias,id",
            "precio_venta_actual"=>"required",
            "stock_minimo"=>"nullable",
            "imagen"=>"nullable",
            "estado"=>"required",
            "fecha_registro"=>"nullable"

        ]);

        //guardar
        $prod=new Producto();
        $prod->nombre=$request->nombre;
        $prod->descripcion=$request->descripcion;
        $prod->codigo_barra=$request->codigo_barra;
        $prod->unidad_medida=$request->unidad_medida;
        $prod->marca=$request->marca;
        $prod->categoria_id=$request->categoria_id;
        $prod->precio_venta_actual=$request->precio_venta_actual;
        $prod->stock_minimo=$request->stock_minimo;
        //"imagen"=>"nullable",
        $prod->estado=$request->estado;
        $prod->fecha_registro=$request->fecha_registro;
        $prod->save();
        //reponder
        return response()->json(["mensaje"=>"producto registrado"]);

    }

    public function actualizarImagen(Request $request,$id){

        $request->validate(
            [
                "imagen"=>"required|image|mimes:jpeg,png,jpg,gif|max:2048"
            ]

    );
    
    if ($file=$request->file("imagen")){
        $direccion_url=time()."-".$file->getClientOriginalName();
        $file->move("imagenes/".$direccion_url);

        $producto=Producto::findOrFail($id);//si no existe, responde 404
        $producto->imagen="imagenes/".$direccion_url;
        $producto->update();

        return response()->json($producto);

    }

    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //mostrar producto con su categoria (404 si no existe)
        $producto=Producto::with("categoria")->findOrFail($id);

        return response()->json($producto);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //validar
        $request->validate([
            "nombre"=>"required",
            "descripcion"=>"nullable",
            "codigo_barra"=>"nullable",
            "unidad_medida"=>"nullable",
            "marca"=>"nullable",
            "categoria_id"=>"required|exists:categorias,id",
            "precio_venta_actual"=>"required",
            "stock_minimo"=>"nullable",
            "imagen"=>"nullable",
            "estado"=>"required",
            "fecha_registro"=>"nullable"
        ]);

        //actualizar (404 si no existe)
        $prod=Producto::findOrFail($id);
        $prod->nombre=$request->nombre;
        $prod->descripcion=$request->descripcion;
        $prod->codigo_barra=$request->codigo_barra;
        $prod->unidad_medida=$request->unidad_medida;
        $prod->marca=$request->marca;
        $prod->categoria_id=$request->categoria_id;
        $prod->precio_venta_actual=$request->precio_venta_actual;
        $prod->stock_minimo=$request->stock_minimo;
        $prod->estado=$request->estado;
        $prod->fecha_registro=$request->fecha_registro;
        $prod->update();

        //responder
        return response()->json(["mensaje"=>"producto actualizado"]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //eliminar (404 si no existe)
        $prod=Producto::findOrFail($id);
        $prod->delete();

        //responder
        return response()->json(["mensaje"=>"producto eliminado"]);
    }

    public function exportarExcell(){
        return Excel::download(new ProductosExport, 'productos.xlsx');
    }
}
