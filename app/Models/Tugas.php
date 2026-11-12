<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tugas extends Model
{
    protected $table = 'tugas';

    protected $fillable = [
        'kelas_id', 'pengajar_id', 'judul', 'deskripsi', 'deadline_at', 'status',
        'published_at', 'max_file_size', 'allowed_file_types',
    ];

    protected function casts(): array
    {
        return [
            'deadline_at' => 'datetime',
            'published_at' => 'datetime',
            'max_file_size' => 'integer',
            'allowed_file_types' => 'array',
        ];
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function pengajar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengajar_id');
    }

    public function rubrik(): HasMany
    {
        return $this->hasMany(Rubrik::class, 'tugas_id');
    }

    public function pengumpulanTugas(): HasMany
    {
        return $this->hasMany(PengumpulanTugas::class, 'tugas_id');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class, 'tugas_id');
    }
}
