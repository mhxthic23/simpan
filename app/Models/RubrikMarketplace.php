<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RubrikMarketplace extends Model
{
    protected $table = 'rubrik_marketplace';

    protected $fillable = [
        'pengajar_id', 'rubrik_id', 'nama', 'deskripsi', 'kategori', 'struktur_rubrik',
        'status', 'is_verified', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'struktur_rubrik' => 'array',
            'is_verified' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function pengajar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengajar_id');
    }

    public function rubrik(): BelongsTo
    {
        return $this->belongsTo(Rubrik::class, 'rubrik_id');
    }
}
