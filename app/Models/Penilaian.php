<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Penilaian extends Model
{
    protected $table = 'penilaian';

    protected $fillable = [
        'pengumpulan_tugas_id', 'pengajar_id', 'status', 'nilai_total', 'feedback', 'graded_at', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'nilai_total' => 'decimal:2',
            'graded_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function pengumpulanTugas(): BelongsTo
    {
        return $this->belongsTo(PengumpulanTugas::class, 'pengumpulan_tugas_id');
    }

    public function pengajar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengajar_id');
    }

    public function detail(): HasMany
    {
        return $this->hasMany(PenilaianDetail::class, 'penilaian_id');
    }

    public function riwayat(): HasMany
    {
        return $this->hasMany(PenilaianRiwayat::class, 'penilaian_id');
    }

    public function disputasi(): HasOne
    {
        return $this->hasOne(Disputasi::class, 'penilaian_id');
    }

    public function votingDao(): HasMany
    {
        return $this->hasMany(VotingDao::class, 'penilaian_id');
    }

    public function blockchainTransactions(): MorphMany
    {
        return $this->morphMany(BlockchainTransaction::class, 'reference', 'reference_type', 'reference_id');
    }

    public function notifikasi(): MorphMany
    {
        return $this->morphMany(Notifikasi::class, 'reference', 'reference_type', 'reference_id');
    }
}
