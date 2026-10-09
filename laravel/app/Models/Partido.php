<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['liga', 'equipo_local', 'equipo_visitante', 'lugar', 'fecha_hora_inicio', 'fecha_hora_fin', 'estado'])]
class Partido extends Model
{
    // Sin cast datetime: la API entrega 'Y-m-d H:i:s' tal cual, como espera Ionic.
    protected $table = 'partidos';

    public function citaciones(): HasMany
    {
        return $this->hasMany(Citacion::class);
    }
}
