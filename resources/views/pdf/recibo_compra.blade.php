<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">

    <title>Recibo de compra</title>

    <style>
        @page {
            margin: 25px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #222;
        }

        .encabezado {
            text-align: center;
            border-bottom: 2px solid #222;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .encabezado h1 {
            font-size: 23px;
            margin: 0 0 8px;
        }

        .codigo {
            font-size: 15px;
            font-weight: bold;
        }

        .seccion {
            margin-bottom: 18px;
        }

        .seccion h3 {
            background: #eeeeee;
            padding: 8px;
            font-size: 12px;
            margin-bottom: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #cccccc;
            padding: 7px;
        }

        th {
            background: #f3f3f3;
            text-align: left;
        }

        .derecha {
            text-align: right;
        }

        .sin-borde td {
            border: none;
            padding: 5px;
        }

        .total {
            font-size: 15px;
            font-weight: bold;
            background: #eeeeee;
        }

        .firma {
            margin-top: 65px;
            text-align: center;
        }

        .linea {
            border-top: 1px solid #222;
            width: 220px;
            margin: 0 auto 8px;
        }

        .pie {
            text-align: center;
            margin-top: 25px;
            font-size: 10px;
        }
    </style>
</head>
<body>

    <div class="encabezado">
        <h1>RECIBO DE COMPRA</h1>
        <div class="codigo">
            {{ $data->codigo }}
        </div>
        <p>
            N.º de compra: {{ $data->id }} |
            Estado: {{ $data->estado }}
        </p>
    </div>

    <div class="seccion">
        <h3>DATOS DE LA COMPRA</h3>

        <table>
            <tr>
                <td><strong>Fecha:</strong></td>
                <td>
                    {{ \Carbon\Carbon::parse($data->fecha)
                        ->format('d/m/Y H:i') }}
                </td>
                <td><strong>Registrado por:</strong></td>
                <td>{{ $data->user->name ?? 'No registrado' }}</td>
            </tr>
            <tr>
                <td><strong>Detalle:</strong></td>
                <td colspan="3">{{ $data->detalle ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="seccion">
        <h3>DATOS DEL PROVEEDOR</h3>

        <table>
            <tr>
                <td><strong>Razón social:</strong></td>
                <td>{{ $data->proveedor->razon_social ?? '-' }}</td>
            </tr>
            <tr>
                <td><strong>Documento:</strong></td>
                <td>{{ $data->proveedor->nro_identificacion ?? '-' }}</td>
            </tr>
            <tr>
                <td><strong>Contacto:</strong></td>
                <td>{{ $data->proveedor->contacto ?? '-' }}</td>
            </tr>
            <tr>
                <td><strong>Teléfono:</strong></td>
                <td>{{ $data->proveedor->telefono ?? '-' }}</td>
            </tr>
            <tr>
                <td><strong>Correo:</strong></td>
                <td>{{ $data->proveedor->correo ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="seccion">
        <h3>DETALLE DE PRODUCTOS</h3>

        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio unitario</th>
                    <th>Importe</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($data->almacen_producto_compra as $producto)
                    @php
                        $cantidad = (float) $producto->pivot->cantidad;
                        $precio = (float)
                            $producto->pivot->precio_unitario_compra;
                        $importe = $cantidad * $precio;
                    @endphp

                    <tr>
                        <td>{{ $producto->nombre }}</td>

                        <td class="derecha">
                            {{ number_format($cantidad, 0) }}
                        </td>

                        <td class="derecha">
                            S/ {{ number_format($precio, 2) }}
                        </td>

                        <td class="derecha">
                            S/ {{ number_format($importe, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="seccion">
        <table class="sin-borde">
            <tr>
                <td class="derecha"><strong>Subtotal:</strong></td>
                <td class="derecha">
                    S/ {{ number_format($subtotal, 2) }}
                </td>
            </tr>

            <tr>
                <td class="derecha"><strong>Descuento:</strong></td>
                <td class="derecha">
                    S/ {{ number_format($descuento, 2) }}
                </td>
            </tr>

            <tr class="total">
                <td class="derecha"><strong>TOTAL:</strong></td>
                <td class="derecha">
                    S/ {{ number_format($total, 2) }}
                </td>
            </tr>
        </table>
    </div>

    <div class="seccion">
        <h3>OBSERVACIONES</h3>
        {{ $data->observaciones ?? 'Sin observaciones' }}
    </div>

    <div class="firma">
        <div class="linea"></div>
        Firma del proveedor
    </div>

    <div class="pie">
        Documento generado por el sistema de inventario.
    </div>

</body>
</html>
