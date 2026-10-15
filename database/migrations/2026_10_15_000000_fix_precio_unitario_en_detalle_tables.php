<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Corrige el typo "precio_unitrio_*" por "precio_unitario_*"
        Schema::table('detalle_venta', function (Blueprint $table) {
            $table->renameColumn('precio_unitrio_venta', 'precio_unitario_venta');
        });

        Schema::table('detalle_compra', function (Blueprint $table) {
            $table->renameColumn('precio_unitrio_compra', 'precio_unitario_compra');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detalle_venta', function (Blueprint $table) {
            $table->renameColumn('precio_unitario_venta', 'precio_unitrio_venta');
        });

        Schema::table('detalle_compra', function (Blueprint $table) {
            $table->renameColumn('precio_unitario_compra', 'precio_unitrio_compra');
        });
    }
};
