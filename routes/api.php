<?php

use App\Http\Controllers\AlmacenController;
use App\Http\Controllers\authController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\VentaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/saludo', function () {
    return 'saludo api';
});

// autenticacion

Route::prefix('v1/auth')->group(function () {// crear grupos

    Route::post('/login', [authController::class, 'login']);

    Route::post('/register', [authController::class, 'register']);

    Route::middleware(['auth:sanctum'])->group(function () {

        Route::get('/profile', [authController::class, 'profile']);

        Route::post('/logout', [authController::class, 'logout']);

    });

});

// CRUD CATEGORIAS(apiResource:GET,POST,GET,PUT,DELETE)

//generar PDF
Route::get("/venta/{id}/generar-pdf",[VentaController::class,"generarReciboPDF"]);

// subida de imagen
Route::post('/producto/{id}/subir-imagen', [ProductoController::class, 'actualizarImagen']); // para subir imagen
Route::apiResource('/categoria', CategoriaController::class);

Route::apiResource('/producto', ProductoController::class);

// CRUD SUCURSALES
Route::apiResource('/sucursal', SucursalController::class);

// CRUD ALMACENES
Route::apiResource('/almacen', AlmacenController::class);

// CRUD CLIENTES
Route::apiResource('/cliente', ClienteController::class);

// CRUD PROVEEDORES
Route::apiResource('/proveedor', ProveedorController::class);

// CRUD COMPRAS Y VENTAS (requieren usuario autenticado)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('/compra', CompraController::class);

    Route::apiResource('/venta', VentaController::class)->parameters(['venta' => 'venta']);
});

Route::get('no-autorizado', function () {
    return ['mensaje' => 'No estas permitido para ver esta pagina'];

})->name('login');
