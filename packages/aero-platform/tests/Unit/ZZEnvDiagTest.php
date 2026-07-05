<?php

namespace Aero\Platform\Tests\Unit;

class ZZEnvDiagTest extends \Tests\TestCase
{
    public function test_resolved_db(): void
    {
        fwrite(STDERR, "\nenv()=".var_export(env('DB_DATABASE'), true)
            ." config.mysql=".var_export(config('database.connections.mysql.database'), true)
            ." default=".config('database.default')
            ." app_env=".app()->environment()."\n");
        $this->assertTrue(true);
    }
}
