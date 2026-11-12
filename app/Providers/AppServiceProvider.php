<?php

namespace App\Providers;

use App\Contracts\AiWritingDetector;
use App\Contracts\BlockchainAdapter;
use App\Contracts\EthereumTransactionSigner;
use App\Contracts\IpfsAdapter;
use App\Contracts\NotificationChannel;
use App\Contracts\PlagiarismChecker;
use App\Services\Integrations\ConfiguredDiagnosticProvider;
use App\Services\Integrations\PinataIpfsAdapter;
use App\Services\Integrations\MailReminderChannel;
use App\Services\Integrations\SepoliaRpcAdapter;
use App\Services\Integrations\UnconfiguredBlockchainAdapter;
use App\Services\Integrations\UnconfiguredEthereumSigner;
use App\Services\Integrations\UnavailableDiagnosticProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(EthereumTransactionSigner::class, UnconfiguredEthereumSigner::class);
        $this->app->bind(BlockchainAdapter::class, function ($app) {
            return config('services.simpan_blockchain.rpc_url')
                ? $app->make(SepoliaRpcAdapter::class)
                : $app->make(UnconfiguredBlockchainAdapter::class);
        });
        $this->app->bind(IpfsAdapter::class, PinataIpfsAdapter::class);
        $this->app->bind(NotificationChannel::class, MailReminderChannel::class);
        $this->app->bind(AiWritingDetector::class, function () {
            if (! config('services.simpan_diagnostics.ai_endpoint') || ! config('services.simpan_diagnostics.ai_key')) {
                return new UnavailableDiagnosticProvider();
            }
            return new ConfiguredDiagnosticProvider('services.simpan_diagnostics.ai_endpoint', 'services.simpan_diagnostics.ai_key');
        });
        $this->app->bind(PlagiarismChecker::class, function () {
            if (! config('services.simpan_diagnostics.plagiarism_endpoint') || ! config('services.simpan_diagnostics.plagiarism_key')) {
                return new UnavailableDiagnosticProvider();
            }
            return new ConfiguredDiagnosticProvider('services.simpan_diagnostics.plagiarism_endpoint', 'services.simpan_diagnostics.plagiarism_key');
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
