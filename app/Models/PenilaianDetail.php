<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenilaianDetail extends Model
{
    protected $table = 'penilaian_detail';

    protected $fillable = ['penilaian_id', 'rubrik_id', 'skor', 'feedback'];

    protected function casts(): array
    {
        return ['skor' => 'decimal:2'];
    }

    public function penilaian(): BelongsTo
    {
        return $this->belongsTo(Penilaian::class, 'penilaian_id');
    }

    public function rubrik(): BelongsTo
    {
        return $this->belongsTo(Rubrik::class, 'rubrik_id');
    }
}
