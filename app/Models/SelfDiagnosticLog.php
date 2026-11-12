<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SelfDiagnosticLog extends Model
{
    protected $table = 'self_diagnostic_log';

    protected $fillable = [
        'pengumpulan_tugas_id', 'siswa_id', 'similarity_score', 'ai_score',
        'plagiarism_result', 'ai_result', 'status', 'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'similarity_score' => 'decimal:2',
            'ai_score' => 'decimal:2',
            'plagiarism_result' => 'array',
            'ai_result' => 'array',
            'checked_at' => 'datetime',
        ];
    }

    public function pengumpulanTugas(): BelongsTo
    {
        return $this->belongsTo(PengumpulanTugas::class, 'pengumpulan_tugas_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'siswa_id');
    }
}
