<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rubrik extends Model
{
    protected $table = 'rubrik';

    protected $fillable = ['tugas_id', 'nama', 'deskripsi', 'bobot', 'skor_maksimum', 'urutan'];

    protected function casts(): array
    {
        return [
            'bobot' => 'decimal:2',
            'skor_maksimum' => 'decimal:2',
            'urutan' => 'integer',
        ];
    }

    public function tugas(): BelongsTo
    {
        return $this->belongsTo(Tugas::class, 'tugas_id');
    }

    public function penilaianDetail(): HasMany
    {
        return $this->hasMany(PenilaianDetail::class, 'rubrik_id');
    }

    public function marketplaceEntries(): HasMany
    {
        return $this->hasMany(RubrikMarketplace::class, 'rubrik_id');
    }
}
