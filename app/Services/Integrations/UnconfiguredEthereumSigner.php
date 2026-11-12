<?php

namespace App\Services\Integrations;

use App\Contracts\EthereumTransactionSigner;
use App\Models\BlockchainTransaction;
use RuntimeException;

class UnconfiguredEthereumSigner implements EthereumTransactionSigner
{
    public function sign(BlockchainTransaction $transaction): string
    {
        throw new RuntimeException('Komponen penandatangan transaksi belum tersedia; private key tidak digunakan.');
    }
}
