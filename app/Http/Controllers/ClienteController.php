<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limite = $request->limit; // para limites
        $buscar = $request->search; // para buscar

        // listado de clientes
        $clientes = Cliente::where(function ($query) use ($buscar) { // agrupar los filtros de busqueda
            $query->where('nombre_completo', 'iLIKE', "%$buscar%") // buscar por nombre_completo
                ->orwhere('nro_identificacion', 'iLIKE', "%$buscar%") // buscar por nro_identificacion
                ->orwhere('correo', 'iLIKE', "%$buscar%"); // buscar por correo
        })
            ->orderby('id', 'DESC') // ordenar descendente
            ->paginate($limite); // paginacion, 10 item por hoja

        return response($clientes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // validar
        $request->validate([
            'nombre_completo' => 'required',
            'nro_identificacion' => 'nullable',
            'fecha_nacimiento' => 'nullable|date',
            'telefono' => 'nullable',
            'correo' => 'nullable|email',
            'estado' => 'nullable|boolean',
        ]);

        // guardar
        $cliente = new Cliente;
        $cliente->nombre_completo = $request->nombre_completo;
        $cliente->nro_identificacion = $request->nro_identificacion;
        $cliente->fecha_nacimiento = $request->fecha_nacimiento;
        $cliente->telefono = $request->telefono;
        $cliente->correo = $request->correo;
        $cliente->estado = $request->estado ?? true;
        $cliente->save();

        // responder
        return response()->json(['mensaje' => 'cliente registrado']);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $cliente = Cliente::find($id);

        return response()->json($cliente);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // validar
        $request->validate([
            'nombre_completo' => 'required',
            'nro_identificacion' => 'nullable',
            'fecha_nacimiento' => 'nullable|date',
            'telefono' => 'nullable',
            'correo' => 'nullable|email',
            'estado' => 'nullable|boolean',
        ]);

        // actualizar
        $cliente = Cliente::find($id);
        $cliente->nombre_completo = $request->nombre_completo;
        $cliente->nro_identificacion = $request->nro_identificacion;
        $cliente->fecha_nacimiento = $request->fecha_nacimiento;
        $cliente->telefono = $request->telefono;
        $cliente->correo = $request->correo;
        $cliente->estado = $request->estado ?? $cliente->estado;
        $cliente->update();

        // responder
        return response()->json(['mensaje' => 'cliente actualizado']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $cliente = Cliente::find($id);
        $cliente->delete();

        return response()->json(['mensaje' => 'cliente eliminado']);
    }
}
