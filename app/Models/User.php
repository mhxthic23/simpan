<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function kelasDiajar(): HasMany
    {
        return $this->hasMany(Kelas::class, 'pengajar_id');
    }

    public function keanggotaanKelas(): HasMany
    {
        return $this->hasMany(KelasSiswa::class, 'siswa_id');
    }

    public function kelasDiikuti(): BelongsToMany
    {
        return $this->belongsToMany(Kelas::class, 'kelas_siswa', 'siswa_id', 'kelas_id')
            ->withPivot('id')
            ->withTimestamps();
    }

    public function tugasDibuat(): HasMany
    {
        return $this->hasMany(Tugas::class, 'pengajar_id');
    }

    public function pengumpulanTugas(): HasMany
    {
        return $this->hasMany(PengumpulanTugas::class, 'siswa_id');
    }

    public function selfDiagnosticLogs(): HasMany
    {
        return $this->hasMany(SelfDiagnosticLog::class, 'siswa_id');
    }

    public function penilaianDibuat(): HasMany
    {
        return $this->hasMany(Penilaian::class, 'pengajar_id');
    }

    public function riwayatPenilaian(): HasMany
    {
        return $this->hasMany(PenilaianRiwayat::class, 'diubah_oleh');
    }

    public function disputasiDiajukan(): HasMany
    {
        return $this->hasMany(Disputasi::class, 'siswa_id');
    }

    public function disputasiDiselesaikan(): HasMany
    {
        return $this->hasMany(Disputasi::class, 'resolved_by');
    }

    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class, 'user_id');
    }

    public function reminderPreferences(): HasOne
    {
        return $this->hasOne(ReminderPreferences::class, 'user_id');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class, 'user_id');
    }

    public function votingDibuat(): HasMany
    {
        return $this->hasMany(VotingDao::class, 'dibuat_oleh');
    }

    public function votingLog(): HasMany
    {
        return $this->hasMany(VotingLog::class, 'user_id');
    }

    public function portfolios(): HasMany
    {
        return $this->hasMany(Portfolio::class, 'siswa_id');
    }

    public function rubrikMarketplace(): HasMany
    {
        return $this->hasMany(RubrikMarketplace::class, 'pengajar_id');
    }

    public function tokenReputasi(): HasMany
    {
        return $this->hasMany(TokenReputasi::class, 'user_id');
    }
}
