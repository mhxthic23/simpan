<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class VotingDao extends Model
{
    protected $table = 'voting_dao';

    protected $fillable = [
        'kelas_id', 'penilaian_id', 'dibuat_oleh', 'judul', 'deskripsi', 'jenis_voting',
        'status', 'starts_at', 'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function penilaian(): BelongsTo
    {
        return $this->belongsTo(Penilaian::class, 'penilaian_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function options(): HasMany
    {
        return $this->hasMany(VotingOption::class, 'voting_dao_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(VotingLog::class, 'voting_dao_id');
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
