<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PengumpulanTugas extends Model
{
    protected $table = 'pengumpulan_tugas';

    protected $fillable = [
        'tugas_id', 'siswa_id', 'attempt_no', 'submitted_at', 'status', 'original_filename',
        'stored_path', 'mime_type', 'file_size', 'ipfs_cid', 'file_hash', 'similarity_score', 'ai_score',
    ];

    protected function casts(): array
    {
        return [
            'attempt_no' => 'integer',
            'submitted_at' => 'datetime',
            'file_size' => 'integer',
            'similarity_score' => 'decimal:2',
            'ai_score' => 'decimal:2',
        ];
    }

    public function tugas(): BelongsTo
    {
        return $this->belongsTo(Tugas::class, 'tugas_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'siswa_id');
    }

    public function penilaian(): HasOne
    {
        return $this->hasOne(Penilaian::class, 'pengumpulan_tugas_id');
    }

    public function selfDiagnosticLogs(): HasMany
    {
        return $this->hasMany(SelfDiagnosticLog::class, 'pengumpulan_tugas_id');
    }

    public function portfolios(): HasMany
    {
        return $this->hasMany(Portfolio::class, 'pengumpulan_tugas_id');
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
