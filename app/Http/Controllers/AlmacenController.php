<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use Illuminate\Http\Request;

class AlmacenController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limite = $request->limit; // para limites
        $buscar = $request->search; // para buscar

        // listado de almacenes
        $almacenes = Almacen::with('sucursal')// traer la sucursal del almacen
            ->where('nombre', 'iLIKE', "%$buscar%")// buscar por nombre
            ->orwhere('codigo', 'iLIKE', "%$buscar%")// buscar por codigo
            ->orwhere('descripcion', 'iLIKE', "%$buscar%")// buscar por descripcion
            ->orderby('id', 'DESC')// ordenar descendente
            ->paginate($limite); // paginacion, 10 item por hoja

        return response($almacenes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // validar
        $request->validate([
            'nombre' => 'required',
            'codigo' => 'nullable',
            'descripcion' => 'nullable',
            'sucursal_id' => 'required|exists:sucursals,id',
        ]);

        // guardar
        $almacen = new Almacen;
        $almacen->nombre = $request->nombre;
        $almacen->codigo = $request->codigo;
        $almacen->descripcion = $request->descripcion;
        $almacen->sucursal_id = $request->sucursal_id;
        $almacen->save();

        // responder
        return response()->json(['mensaje' => 'almacen registrado']);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $almacen = Almacen::with('sucursal')->find($id);

        return response()->json($almacen);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // validar
        $request->validate([
            'nombre' => 'required',
            'codigo' => 'nullable',
            'descripcion' => 'nullable',
            'sucursal_id' => 'required|exists:sucursals,id',
        ]);

        // actualizar
        $almacen = Almacen::find($id);
        $almacen->nombre = $request->nombre;
        $almacen->codigo = $request->codigo;
        $almacen->descripcion = $request->descripcion;
        $almacen->sucursal_id = $request->sucursal_id;
        $almacen->update();

        // responder
        return response()->json(['mensaje' => 'almacen actualizado']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $almacen = Almacen::find($id);
        $almacen->delete();

        return response()->json(['mensaje' => 'almacen eliminado']);
    }
}
