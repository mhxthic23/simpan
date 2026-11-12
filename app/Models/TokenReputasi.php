<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TokenReputasi extends Model
{
    protected $table = 'token_reputasi';

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'jenis_aktivitas', 'jumlah_token',
        'reference_type', 'reference_id', 'keterangan', 'idempotency_key', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_token' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }
}
