<?php

declare(strict_types=1);

namespace Aero\Assistant\Console\Commands;

use Aero\Assistant\Services\IndexingService;
use Illuminate\Console\Command;

class IndexKnowledge extends Command
{
    protected $signature = 'aeon:index {--fresh : Clear the index and rebuild from scratch}';

    protected $description = "Build Aeon's knowledge base (modules + curated docs) for grounded answers";

    public function handle(IndexingService $indexer): int
    {
        $this->info('Indexing Aeon knowledge base'.($this->option('fresh') ? ' (fresh)…' : '…'));

        $result = $indexer->index((bool) $this->option('fresh'));

        $this->line("  Sources: {$result['sources']}");
        $this->line("  Embedded: {$result['indexed']}");
        $this->line("  Skipped (unchanged): {$result['skipped']}");
        $this->info('Done.');

        return self::SUCCESS;
    }
}
