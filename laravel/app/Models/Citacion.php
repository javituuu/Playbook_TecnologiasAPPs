<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'partido_id', 'mensaje_coach', 'estado_respuesta'])]
class Citacion extends Model
{
    protected $table = 'citaciones';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function partido(): BelongsTo
    {
        return $this->belongsTo(Partido::class);
    }
}
