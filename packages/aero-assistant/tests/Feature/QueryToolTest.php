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
                return [
                    'aeon_test_widgets' => [
                        'table' => 'aeon_test_widgets',
                        'label' => 'Widgets',
                        'columns' => ['id', 'status', 'price', 'category_id', 'is_active', 'created_at'],
                        'date_fields' => ['created_at'],
                        'soft_delete' => false,
                    ],
                    'categories' => [
                        'table' => 'aeon_test_categories',
                        'label' => 'Categories',
                        'columns' => ['id', 'name'],
                        'date_fields' => [],
                        'soft_delete' => false,
                    ],
                ];
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
            $t->unsignedBigInteger('category_id')->nullable();
            $t->boolean('is_active')->default(1);
            $t->timestamp('created_at')->nullable();
        });
        Schema::create('aeon_test_categories', function ($t) {
            $t->id();
            $t->string('name');
        });
        \Illuminate\Support\Facades\DB::table('aeon_test_categories')->insert([
            ['id' => 1, 'name' => 'Alpha'], ['id' => 2, 'name' => 'Beta'],
        ]);
        AeonQueryWidget::insert([
            ['status' => 'open', 'price' => 10, 'category_id' => 1, 'created_at' => now()],
            ['status' => 'open', 'price' => 20, 'category_id' => 1, 'created_at' => now()],
            ['status' => 'closed', 'price' => 30, 'category_id' => 2, 'created_at' => now()],
        ]);
    }

    public function test_group_by_with_donut_chart(): void
    {
        $out = (new QueryTool($this->catalog()))->run(
            ['entity' => 'aeon_test_widgets', 'operation' => 'count', 'group_by' => 'status', 'chart' => 'donut'],
            1,
        );
        $donut = (new Collection($out['blocks']))->firstWhere('type', 'donut');
        $this->assertNotNull($donut);
        $this->assertSame(['label' => 'open', 'value' => 2], $donut['items'][0]);
    }

    public function test_group_by_foreign_key_resolves_related_names(): void
    {
        $out = (new QueryTool($this->catalog()))->run(
            ['entity' => 'aeon_test_widgets', 'operation' => 'count', 'group_by' => 'category_id'],
            1,
        );
        $bar = (new Collection($out['blocks']))->firstWhere('type', 'bar');
        $this->assertNotNull($bar);
        // category 1 (Alpha) has 2, category 2 (Beta) has 1 — ids resolved to names.
        $this->assertSame(['label' => 'Alpha', 'value' => 2], $bar['items'][0]);
        $this->assertSame(['label' => 'Beta', 'value' => 1], $bar['items'][1]);
    }

    public function test_count_total_emits_stat_block(): void
    {
        $out = (new QueryTool($this->catalog()))->run(['entity' => 'aeon_test_widgets', 'operation' => 'count'], 1);
        $stats = (new Collection($out['blocks']))->firstWhere('type', 'stats');
        $this->assertSame('3', $stats['items'][0]['v']);
    }

    public function test_count_group_by_emits_bar_block(): void
    {
        $out = (new QueryTool($this->catalog()))->run(
            ['entity' => 'aeon_test_widgets', 'operation' => 'count', 'group_by' => 'status'],
            1,
        );
        $bar = (new Collection($out['blocks']))->firstWhere('type', 'bar');
        $this->assertNotNull($bar);
        // open (2) ranks above closed (1)
        $this->assertSame(['label' => 'open', 'value' => 2], $bar['items'][0]);
        $this->assertSame(['label' => 'closed', 'value' => 1], $bar['items'][1]);
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

    public function test_filter_by_column_value(): void
    {
        $out = (new QueryTool($this->catalog()))->run([
            'entity' => 'aeon_test_widgets', 'operation' => 'count',
            'filters' => [['column' => 'status', 'op' => 'eq', 'value' => 'open']],
        ], 1);
        $stats = (new Collection($out['blocks']))->firstWhere('type', 'stats');
        $this->assertSame('2', $stats['items'][0]['v']); // 2 open
    }

    public function test_filter_by_foreign_key_name_resolves(): void
    {
        $out = (new QueryTool($this->catalog()))->run([
            'entity' => 'aeon_test_widgets', 'operation' => 'count',
            'filters' => [['column' => 'category_id', 'op' => 'eq', 'value' => 'Alpha']],
        ], 1);
        $stats = (new Collection($out['blocks']))->firstWhere('type', 'stats');
        $this->assertSame('2', $stats['items'][0]['v']); // Alpha = id 1 = 2 widgets
    }

    public function test_find_returns_entity_card_with_resolved_fields(): void
    {
        $out = (new QueryTool($this->catalog()))->run([
            'entity' => 'aeon_test_widgets', 'operation' => 'find',
            'filters' => [['column' => 'status', 'op' => 'eq', 'value' => 'closed']],
        ], 1);
        $card = (new Collection($out['blocks']))->firstWhere('type', 'entityCard');
        $this->assertNotNull($card);
        $fields = new Collection($card['fields']);
        $this->assertTrue($fields->contains(fn ($f) => $f['k'] === 'Status' && $f['v'] === 'closed'));
        // category_id (2) resolved to its name "Beta"
        $this->assertTrue($fields->contains(fn ($f) => $f['v'] === 'Beta'));
        // is_active (1) formatted as a friendly boolean
        $this->assertTrue($fields->contains(fn ($f) => $f['k'] === 'Is Active' && $f['v'] === 'Yes'));
    }

    public function test_unknown_entity_is_rejected(): void
    {
        $out = (new QueryTool($this->catalog()))->run(['entity' => 'nope', 'operation' => 'count'], 1);
        $this->assertSame([], $out['blocks']);
    }
}
