<?php

namespace Aero\Blockchain\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class BlockchainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/blockchain.php', 'blockchain'
        );
    }

    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');


        // Register publishable assets
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/blockchain.php' => config_path('blockchain.php'),
            ], 'blockchain-config');

            $this->publishes([
                __DIR__.'/../../database/migrations' => database_path('migrations'),
            ], 'blockchain-migrations');

            $this->publishes([
                __DIR__.'/../../resources/js' => resource_path('js/blockchain'),
            ], 'blockchain-assets');
        }

        // Register morph map for polymorphic relations
        Relation::morphMap([
            'blockchain' => \Aero\Blockchain\Models\Blockchain::class,
            'block' => \Aero\Blockchain\Models\Block::class,
            'transaction' => \Aero\Blockchain\Models\Transaction::class,
            'wallet' => \Aero\Blockchain\Models\Wallet::class,
            'smart_contract' => \Aero\Blockchain\Models\SmartContract::class,
            'token' => \Aero\Blockchain\Models\CryptocurrencyToken::class,
        ]);

        // Register blockchain event listeners
        $this->registerEventListeners();

        // Register blockchain commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                // Blockchain-specific Artisan commands would go here
                // \Aero\Blockchain\Console\Commands\SyncBlockchainCommand::class,
                // \Aero\Blockchain\Console\Commands\ProcessTransactionsCommand::class,
                // \Aero\Blockchain\Console\Commands\ValidateBlocksCommand::class,
            ]);
        }
    }

    protected function registerEventListeners(): void
    {
        // Register blockchain-specific event listeners
        // Example: Block mined, transaction confirmed, etc.

        // Event::listen(BlockMined::class, ProcessBlockListener::class);
        // Event::listen(TransactionConfirmed::class, UpdateBalanceListener::class);
        // Event::listen(SmartContractDeployed::class, IndexContractListener::class);
    }
}
