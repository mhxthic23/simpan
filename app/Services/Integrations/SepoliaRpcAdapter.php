<?php

namespace App\Services\Integrations;

use App\Contracts\BlockchainAdapter;
use App\Contracts\EthereumTransactionSigner;
use App\Models\BlockchainTransaction;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/** RPC receipt adapter. A separately audited signer must supply signed raw transactions before submit is enabled. */
class SepoliaRpcAdapter implements BlockchainAdapter
{
    public function __construct(private readonly EthereumTransactionSigner $signer) {}

    public function submit(BlockchainTransaction $transaction): array
    {
        $chainId = $this->rpc('eth_chainId', []);
        if (! is_string($chainId) || hexdec($chainId) !== (int) config('services.simpan_blockchain.chain_id')) {
            throw new RuntimeException('RPC yang dikonfigurasi bukan jaringan Sepolia dengan chain ID yang diharapkan.');
        }
        $raw = $this->signer->sign($transaction);
        if (! str_starts_with($raw, '0x')) throw new RuntimeException('Signer tidak menghasilkan signed raw transaction yang valid.');

        $result = $this->rpc('eth_sendRawTransaction', [$raw]);
        if (! is_string($result) || ! preg_match('/^0x[a-fA-F0-9]{64}$/', $result)) {
            throw new RuntimeException('RPC Sepolia mengembalikan transaction hash yang tidak valid.');
        }

        return ['transaction_hash' => strtolower($result), 'block_number' => null];
    }

    public function receipt(string $transactionHash): array
    {
        $receipt = $this->rpc('eth_getTransactionReceipt', [$transactionHash]);
        if ($receipt === null) {
            return ['confirmed' => false, 'block_number' => null];
        }
        if (! is_array($receipt) || ! isset($receipt['status'], $receipt['blockNumber'])) {
            throw new RuntimeException('Format receipt dari RPC Sepolia tidak valid.');
        }

        return [
            'confirmed' => hexdec((string) $receipt['status']) === 1,
            'failed' => hexdec((string) $receipt['status']) === 0,
            'block_number' => hexdec((string) $receipt['blockNumber']),
        ];
    }

    private function rpc(string $method, array $params): mixed
    {
        $url = (string) config('services.simpan_blockchain.rpc_url');
        if ($url === '') {
            throw new RuntimeException('BLOCKCHAIN_RPC_URL belum dikonfigurasi.');
        }
        try {
            $response = Http::timeout(15)->post($url, [
                'jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params,
            ])->throw()->json();
        } catch (Throwable) {
            throw new RuntimeException('Tidak dapat menghubungi atau memproses respons RPC Sepolia.');
        }
        if (isset($response['error'])) {
            $code = isset($response['error']['code']) && is_numeric($response['error']['code']) ? (int) $response['error']['code'] : 'unknown';
            throw new RuntimeException('RPC Sepolia menolak permintaan (kode '.$code.').');
        }

        return $response['result'] ?? null;
    }
}
