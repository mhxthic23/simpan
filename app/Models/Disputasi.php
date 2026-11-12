<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Disputasi extends Model
{
    protected $table = 'disputasi';

    protected $fillable = [
        'penilaian_id', 'siswa_id', 'alasan', 'bukti_path', 'status', 'tanggapan_pengajar',
        'keputusan', 'resolved_by', 'submitted_at', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function penilaian(): BelongsTo
    {
        return $this->belongsTo(Penilaian::class, 'penilaian_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'siswa_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function blockchainTransactions(): MorphMany
    {
        return $this->morphMany(BlockchainTransaction::class, 'reference', 'reference_type', 'reference_id');
    }

    public function notifikasi(): MorphMany
    {
        return $this->morphMany(Notifikasi::class, 'reference', 'reference_type', 'reference_id');
    }

    public function riwayat(): HasMany
    {
        return $this->hasMany(DisputasiRiwayat::class, 'disputasi_id')->orderBy('created_at');
    }
}
