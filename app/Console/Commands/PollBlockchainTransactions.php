<?php

namespace App\Console\Commands;

use App\Contracts\BlockchainAdapter;
use App\Models\BlockchainTransaction;
use Illuminate\Console\Command;
use Throwable;

class PollBlockchainTransactions extends Command
{
    protected $signature = 'simpan:blockchain-poll';
    protected $description = 'Memeriksa receipt transaksi blockchain SIMPAN yang telah dikirim.';

    public function handle(BlockchainAdapter $adapter): int
    {
        BlockchainTransaction::query()->where('status', 'submitted')->whereNotNull('transaction_hash')
            ->orderBy('id')->chunkById(100, function ($transactions) use ($adapter): void {
                foreach ($transactions as $transaction) {
                    try {
                        $receipt = $adapter->receipt($transaction->transaction_hash);
                        if ($receipt['failed'] ?? false) {
                            $transaction->forceFill(['status' => 'failed', 'block_number' => $receipt['block_number'] ?? null,
                                'failed_at' => now(), 'error_message' => 'Transaksi tercatat tetapi eksekusi kontrak gagal.'])->save();
                            continue;
                        }
                        if (! $receipt['confirmed']) continue;
                        $transaction->forceFill([
                            'status' => 'confirmed',
                            'block_number' => $receipt['block_number'] ?? $transaction->block_number,
                            'confirmed_at' => now(),
                            'failed_at' => null,
                            'error_message' => null,
                        ])->save();
                    } catch (Throwable $exception) {
                        logger()->error('Pemeriksaan receipt blockchain gagal.', ['transaction_id' => $transaction->id, 'exception_class' => $exception::class]);
                    }
                }
            });
        return self::SUCCESS;
    }
}
