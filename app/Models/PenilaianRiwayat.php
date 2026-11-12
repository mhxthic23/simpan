<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenilaianRiwayat extends Model
{
    protected $table = 'penilaian_riwayat';

    protected $fillable = [
        'penilaian_id', 'diubah_oleh', 'nilai_sebelumnya', 'nilai_baru', 'alasan_perubahan', 'snapshot_data',
    ];

    protected function casts(): array
    {
        return [
            'nilai_sebelumnya' => 'decimal:2',
            'nilai_baru' => 'decimal:2',
            'snapshot_data' => 'array',
        ];
    }

    public function penilaian(): BelongsTo
    {
        return $this->belongsTo(Penilaian::class, 'penilaian_id');
    }

    public function pengubah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diubah_oleh');
    }
}
