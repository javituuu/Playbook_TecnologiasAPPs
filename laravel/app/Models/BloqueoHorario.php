<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'dia_semana', 'hora_inicio', 'hora_fin'])]
class BloqueoHorario extends Model
{
    protected $table = 'bloqueos_horarios';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
