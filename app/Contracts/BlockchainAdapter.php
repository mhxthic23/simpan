<?php

namespace App\Contracts;

use App\Models\BlockchainTransaction;

interface BlockchainAdapter
{
    /** @return array{transaction_hash: string, block_number?: int|null} */
    public function submit(BlockchainTransaction $transaction): array;

    /** @return array{confirmed: bool, failed?: bool, block_number?: int|null} */
    public function receipt(string $transactionHash): array;
}
