<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;

class ProveedorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limite = $request->limit; // para limites
        $buscar = $request->search; // para buscar

        // listado de proveedores
        $proveedores = Proveedor::where(function ($query) use ($buscar) { // agrupar los filtros de busqueda
            $query->where('razon_social', 'iLIKE', "%$buscar%") // buscar por razon_social
                ->orwhere('nro_identificacion', 'iLIKE', "%$buscar%") // buscar por nro_identificacion
                ->orwhere('contacto', 'iLIKE', "%$buscar%") // buscar por contacto
                ->orwhere('correo', 'iLIKE', "%$buscar%"); // buscar por correo
        })
            ->orderby('id', 'DESC') // ordenar descendente
            ->paginate($limite); // paginacion, 10 item por hoja

        return response($proveedores);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // validar
        $request->validate([
            'razon_social' => 'required',
            'nro_identificacion' => 'nullable',
            'contacto' => 'nullable',
            'telefono' => 'nullable',
            'correo' => 'nullable|email',
            'observaciones' => 'nullable',
            'estado' => 'nullable|boolean',
        ]);

        // guardar
        $proveedor = new Proveedor;
        $proveedor->razon_social = $request->razon_social;
        $proveedor->nro_identificacion = $request->nro_identificacion;
        $proveedor->contacto = $request->contacto;
        $proveedor->telefono = $request->telefono;
        $proveedor->correo = $request->correo;
        $proveedor->observaciones = $request->observaciones;
        $proveedor->estado = $request->estado ?? true;
        $proveedor->save();

        // responder
        return response()->json(['mensaje' => 'proveedor registrado']);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $proveedor = Proveedor::find($id);

        return response()->json($proveedor);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // validar
        $request->validate([
            'razon_social' => 'required',
            'nro_identificacion' => 'nullable',
            'contacto' => 'nullable',
            'telefono' => 'nullable',
            'correo' => 'nullable|email',
            'observaciones' => 'nullable',
            'estado' => 'nullable|boolean',
        ]);

        // actualizar
        $proveedor = Proveedor::find($id);
        $proveedor->razon_social = $request->razon_social;
        $proveedor->nro_identificacion = $request->nro_identificacion;
        $proveedor->contacto = $request->contacto;
        $proveedor->telefono = $request->telefono;
        $proveedor->correo = $request->correo;
        $proveedor->observaciones = $request->observaciones;
        $proveedor->estado = $request->estado ?? $proveedor->estado;
        $proveedor->update();

        // responder
        return response()->json(['mensaje' => 'proveedor actualizado']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $proveedor = Proveedor::find($id);
        $proveedor->delete();

        return response()->json(['mensaje' => 'proveedor eliminado']);
    }
}
