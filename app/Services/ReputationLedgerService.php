<?php

namespace App\Services;

use App\Models\TokenReputasi;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ReputationLedgerService
{
    public function record(User $user, string $activity, int $delta, string $idempotencyKey, ?string $description = null, ?Model $reference = null): TokenReputasi
    {
        if ($delta === 0) throw new \InvalidArgumentException('Perubahan token harus bukan nol.');
        return DB::transaction(function () use ($user, $activity, $delta, $idempotencyKey, $description, $reference): TokenReputasi {
            try {
                return TokenReputasi::query()->firstOrCreate(['idempotency_key' => mb_substr($idempotencyKey, 0, 100)], [
                    'user_id' => $user->id, 'jenis_aktivitas' => mb_substr($activity, 0, 50),
                    'jumlah_token' => $delta, 'reference_type' => $reference?->getMorphClass(),
                    'reference_id' => $reference?->getKey(), 'keterangan' => $description,
                    'created_at' => now(),
                ]);
            } catch (QueryException $exception) {
                $existing = TokenReputasi::query()->where('idempotency_key', mb_substr($idempotencyKey, 0, 100))->first();
                if ($existing) return $existing;
                throw $exception;
            }
        });
    }

    public function balance(User $user): int
    {
        return (int) TokenReputasi::query()->where('user_id', $user->id)->sum('jumlah_token');
    }
}
