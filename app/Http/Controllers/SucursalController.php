<?php

namespace App\Http\Controllers;

use App\Models\Sucursal;
use Illuminate\Http\Request;

class SucursalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limite = $request->limit; // para limites
        $buscar = $request->search; // para buscar

        // listado de sucursales
        $sucursales = Sucursal::where('nombre', 'iLIKE', "%$buscar%")// buscar por nombre
            ->orwhere('direccion', 'iLIKE', "%$buscar%")// buscar por direccion
            ->orwhere('ciudad', 'iLIKE', "%$buscar%")// buscar por ciudad
            ->orderby('id', 'DESC')// ordenar descendente
            ->paginate($limite); // paginacion, 10 item por hoja

        return response($sucursales);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // validar
        $request->validate([
            'nombre' => 'required',
            'direccion' => 'required',
            'telefono' => 'nullable',
            'ciudad' => 'required',
        ]);

        // guardar
        $sucursal = new Sucursal;
        $sucursal->nombre = $request->nombre;
        $sucursal->direccion = $request->direccion;
        $sucursal->telefono = $request->telefono;
        $sucursal->ciudad = $request->ciudad;
        $sucursal->save();

        // responder
        return response()->json(['mensaje' => 'sucursal registrada']);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $sucursal = Sucursal::find($id);

        return response()->json($sucursal);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // validar
        $request->validate([
            'nombre' => 'required',
            'direccion' => 'required',
            'telefono' => 'nullable',
            'ciudad' => 'required',
        ]);

        // actualizar
        $sucursal = Sucursal::find($id);
        $sucursal->nombre = $request->nombre;
        $sucursal->direccion = $request->direccion;
        $sucursal->telefono = $request->telefono;
        $sucursal->ciudad = $request->ciudad;
        $sucursal->update();

        // responder
        return response()->json(['mensaje' => 'sucursal actualizada']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $sucursal = Sucursal::find($id);
        $sucursal->delete();

        return response()->json(['mensaje' => 'sucursal eliminada']);
    }
}
