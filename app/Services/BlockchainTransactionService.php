<?php

namespace App\Services;

use App\Jobs\SubmitBlockchainTransaction;
use App\Models\BlockchainTransaction;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BlockchainTransactionService
{
    /** Only the digest is persisted or sent; callers must provide non-sensitive event metadata. */
    public function record(string $eventType, Model $reference, array $safePayload, string $idempotencyKey): BlockchainTransaction
    {
        $payloadHash = hash('sha256', json_encode($this->canonicalize($safePayload), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $attributes = [
            'event_type' => substr($eventType, 0, 50),
            'reference_type' => $reference->getMorphClass(),
            'reference_id' => $reference->getKey(),
            'network' => (string) config('services.simpan_blockchain.network', 'sepolia'),
            'chain_id' => (int) config('services.simpan_blockchain.chain_id', 11155111),
            'contract_address' => config('services.simpan_blockchain.contract_address'),
            'payload_hash' => $payloadHash,
            'status' => 'pending',
            'idempotency_key' => substr($idempotencyKey, 0, 100),
        ];

        try {
            $transaction = BlockchainTransaction::query()->firstOrCreate(
                ['idempotency_key' => $attributes['idempotency_key']],
                $attributes
            );
        } catch (QueryException $exception) {
            $transaction = BlockchainTransaction::query()->where('idempotency_key', $attributes['idempotency_key'])->first();
            if (! $transaction) throw $exception;
        }

        if ($transaction->wasRecentlyCreated) {
            if ($this->isConfigured()) {
                DB::afterCommit(fn () => SubmitBlockchainTransaction::dispatch($transaction->id));
            } else {
                $transaction->forceFill([
                    'status' => 'failed',
                    'failed_at' => now(),
                    'error_message' => 'Integrasi Sepolia belum dikonfigurasi; tidak ada transaksi yang dikirim.',
                ])->save();
            }
        }

        return $transaction;
    }

    public function retry(BlockchainTransaction $transaction): void
    {
        abort_unless($this->isConfigured(), 503, 'Integrasi blockchain belum dikonfigurasi.');
        if ($transaction->status === 'confirmed' || $transaction->transaction_hash !== null) {
            abort(409, 'Transaksi yang telah dikirim atau dikonfirmasi tidak dapat dikirim ulang dengan idempotency yang sama.');
        }
        $transaction->forceFill(['status' => 'pending', 'failed_at' => null, 'error_message' => null])->save();
        SubmitBlockchainTransaction::dispatch($transaction->id);
    }

    private function isConfigured(): bool
    {
        return filled(config('services.simpan_blockchain.rpc_url'))
            && filled(config('services.simpan_blockchain.contract_address'))
            && filled(config('services.simpan_blockchain.wallet_address'))
            && filled(config('services.simpan_blockchain.private_key'));
    }

    private function canonicalize(array $value): array
    {
        ksort($value);
        foreach ($value as &$item) if (is_array($item)) $item = $this->canonicalize($item);
        return $value;
    }
}
