<?php

namespace Aero\Core\Tests\Unit\Http;

use Aero\Kernel\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * Phase 0 Task 10 — boundedPerPage() helper coverage.
 *
 * Asserts the helper:
 *   - returns the default when ?per_page is omitted
 *   - caps unbounded user values at the configured max
 *   - respects user values within bounds
 *   - clamps zero/negative values to the floor (1)
 */
class BoundedPerPageTest extends TestCase
{
    private function controllerWithRequest(Request $request): int
    {
        $controller = new class extends Controller {
            public function call(Request $r, int $default = 20, int $max = 100): int
            {
                return $this->boundedPerPage($r, $default, $max);
            }
        };

        return $controller->call($request);
    }

    public function test_default_is_20_when_per_page_missing(): void
    {
        $this->assertSame(20, $this->controllerWithRequest(new Request()));
    }

    public function test_caps_unbounded_value_at_100(): void
    {
        $this->assertSame(100, $this->controllerWithRequest(new Request(['per_page' => 999999])));
    }

    public function test_respects_user_value_within_bounds(): void
    {
        $this->assertSame(50, $this->controllerWithRequest(new Request(['per_page' => 50])));
    }

    public function test_clamps_zero_to_floor_of_1(): void
    {
        $this->assertSame(1, $this->controllerWithRequest(new Request(['per_page' => 0])));
    }

    public function test_clamps_negative_to_floor_of_1(): void
    {
        $this->assertSame(1, $this->controllerWithRequest(new Request(['per_page' => -10])));
    }

    public function test_coerces_string_input_to_int(): void
    {
        $this->assertSame(25, $this->controllerWithRequest(new Request(['per_page' => '25'])));
    }
}
