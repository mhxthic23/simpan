<?php

namespace App\Contracts;

use App\Models\BlockchainTransaction;

interface EthereumTransactionSigner
{
    public function sign(BlockchainTransaction $transaction): string;
}
