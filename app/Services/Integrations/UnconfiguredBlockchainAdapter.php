<?php

namespace App\Services\Integrations;

use App\Contracts\BlockchainAdapter;
use App\Models\BlockchainTransaction;
use RuntimeException;

class UnconfiguredBlockchainAdapter implements BlockchainAdapter
{
    public function submit(BlockchainTransaction $transaction): array
    {
        throw new RuntimeException('Penandatangan transaksi Ethereum belum dikonfigurasi.');
    }

    public function receipt(string $transactionHash): array
    {
        throw new RuntimeException('RPC Sepolia belum dikonfigurasi.');
    }
}
