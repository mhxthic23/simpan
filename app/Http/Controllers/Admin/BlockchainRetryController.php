<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockchainTransaction;
use App\Services\BlockchainTransactionService;
use Illuminate\Http\RedirectResponse;

class BlockchainRetryController extends Controller
{
    public function retry(BlockchainTransaction $transaction, BlockchainTransactionService $service): RedirectResponse
    {
        if (! config('services.simpan_blockchain.rpc_url') || ! config('services.simpan_blockchain.contract_address') || ! config('services.simpan_blockchain.wallet_address')) {
            return back()->withErrors(['blockchain' => 'RPC, wallet, dan contract Sepolia harus dikonfigurasi sebelum retry.']);
        }
        try {
            $service->retry($transaction);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            return back()->withErrors(['blockchain' => $exception->getMessage()]);
        }
        return back()->with('status', 'Transaksi dimasukkan kembali ke antrean. Status akan berubah setelah dikirim dan dikonfirmasi jaringan.');
    }
}
