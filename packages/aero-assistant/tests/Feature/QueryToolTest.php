<?php

declare(strict_types=1);

namespace Aero\Assistant\Tests\Feature;

use Aero\Assistant\Data\QueryTool;
use Aero\Assistant\Data\SchemaCatalog;
use Aero\Assistant\Tests\PackageTestCase;
use Aero\Contracts\Models\TenantModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AeonQueryWidget extends TenantModel
{
    protected $table = 'aeon_test_widgets';

    public $timestamps = false;

    protected $guarded = [];
}

class QueryToolTest extends PackageTestCase
{
    private function catalog(): SchemaCatalog
    {
        return new class extends SchemaCatalog
        {
            public function all(): array
            {
                return ['aeon_test_widgets' => [
                    'model' => AeonQueryWidget::class,
                    'table' => 'aeon_test_widgets',
                    'label' => 'Widgets',
                    'columns' => ['id', 'status', 'price', 'created_at'],
                    'date_fields' => ['created_at'],
                ]];
            }
        };
    }

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('aeon_test_widgets', function ($t) {
            $t->id();
            $t->string('status');
            $t->integer('price');
            $t->timestamp('created_at')->nullable();
        });
        AeonQueryWidget::insert([
            ['status' => 'open', 'price' => 10, 'created_at' => now()],
            ['status' => 'open', 'price' => 20, 'created_at' => now()],
            ['status' => 'closed', 'price' => 30, 'created_at' => now()],
        ]);
    }

    public function test_count_total_emits_stat_block(): void
    {
        $out = (new QueryTool($this->catalog()))->run(['entity' => 'aeon_test_widgets', 'operation' => 'count'], 1);
        $stats = (new Collection($out['blocks']))->firstWhere('type', 'stats');
        $this->assertSame('3', $stats['items'][0]['v']);
    }

    public function test_count_group_by_emits_table_block(): void
    {
        $out = (new QueryTool($this->catalog()))->run(
            ['entity' => 'aeon_test_widgets', 'operation' => 'count', 'group_by' => 'status'],
            1,
        );
        $table = (new Collection($out['blocks']))->firstWhere('type', 'table');
        $this->assertNotNull($table);
        // open (2) ranks above closed (1)
        $this->assertSame(['open', '2'], $table['rows'][0]);
        $this->assertSame(['closed', '1'], $table['rows'][1]);
    }

    public function test_aggregate_sum(): void
    {
        $out = (new QueryTool($this->catalog()))->run(
            ['entity' => 'aeon_test_widgets', 'operation' => 'aggregate', 'aggregate' => 'sum', 'column' => 'price'],
            1,
        );
        $stats = (new Collection($out['blocks']))->firstWhere('type', 'stats');
        $this->assertSame('60', $stats['items'][0]['v']);
    }

    public function test_unknown_entity_is_rejected(): void
    {
        $out = (new QueryTool($this->catalog()))->run(['entity' => 'nope', 'operation' => 'count'], 1);
        $this->assertSame([], $out['blocks']);
    }
}
