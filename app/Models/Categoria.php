<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    //conecta por defecto con la tabla categorias

    public function productos(){
        return $this->hasMany(producto::class);
    }
    
}
