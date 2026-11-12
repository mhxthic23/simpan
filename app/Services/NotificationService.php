<?php

namespace App\Services;

use App\Models\Notifikasi;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class NotificationService
{
    private ?bool $supportsIdempotency = null;

    public function sendOnce(User $recipient, string $title, string $message, string $type, string $key, ?Model $reference = null): Notifikasi
    {
        if ($this->supportsIdempotency ??= Schema::hasColumn('notifikasi', 'idempotency_key')) {
            // continue with the uniquely constrained outbox key below
        } else {
            logger()->warning('Notifikasi tidak dibuat karena migration idempotency_key belum diterapkan.');
            return new Notifikasi();
        }
        $attributes = [
            'user_id' => $recipient->id,
            'judul' => mb_substr($title, 0, 255),
            'pesan' => $message,
            'tipe' => mb_substr($type, 0, 50),
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'idempotency_key' => mb_substr($key, 0, 100),
        ];

        try {
            return Notifikasi::query()->firstOrCreate(['idempotency_key' => $attributes['idempotency_key']], $attributes);
        } catch (QueryException $exception) {
            $notification = Notifikasi::query()->where('idempotency_key', $attributes['idempotency_key'])->first();
            if ($notification) return $notification;
            throw $exception;
        }
    }
}
