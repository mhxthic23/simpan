<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisputasiRiwayat extends Model
{
    public $timestamps = false;

    protected $table = 'disputasi_riwayat';

    protected $fillable = [
        'disputasi_id', 'diubah_oleh', 'status_sebelumnya', 'status_baru',
        'tanggapan', 'keputusan', 'snapshot_data', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_data' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function disputasi(): BelongsTo
    {
        return $this->belongsTo(Disputasi::class, 'disputasi_id');
    }

    public function pengubah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diubah_oleh');
    }
}
