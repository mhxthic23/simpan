<?php

namespace App\Jobs;

use App\Contracts\BlockchainAdapter;
use App\Models\BlockchainTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SubmitBlockchainTransaction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 60, 180];

    public function __construct(public readonly int $transactionId) {}

    public function handle(BlockchainAdapter $adapter): void
    {
        $transaction = BlockchainTransaction::query()->lockForUpdate()->findOrFail($this->transactionId);
        if (in_array($transaction->status, ['confirmed', 'submitted'], true)) return;

        $transaction->increment('attempt_count');
        try {
            $result = $adapter->submit($transaction->fresh());
            $transaction->forceFill([
                'transaction_hash' => strtolower($result['transaction_hash']),
                'block_number' => $result['block_number'] ?? null,
                'status' => 'submitted',
                'submitted_at' => now(),
                'failed_at' => null,
                'error_message' => null,
            ])->save();
        } catch (Throwable $exception) {
            $transaction->forceFill(['error_message' => 'Pengiriman blockchain gagal; periksa konfigurasi dan log operasional.'])->save();
            logger()->error('Pengiriman blockchain gagal.', ['transaction_id' => $transaction->id, 'exception_class' => $exception::class]);
            throw new \RuntimeException('Pengiriman blockchain gagal; periksa konfigurasi dan log operasional.');
        }
    }

    public function failed(Throwable $exception): void
    {
        BlockchainTransaction::query()->whereKey($this->transactionId)
            ->whereNotIn('status', ['confirmed', 'submitted'])
            ->update(['status' => 'failed', 'failed_at' => now(), 'error_message' => 'Pengiriman blockchain gagal; periksa konfigurasi dan log operasional.']);
    }
}
