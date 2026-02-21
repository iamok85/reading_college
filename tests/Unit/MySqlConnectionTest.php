<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MySqlConnectionTest extends TestCase
{
    public function test_mysql_connection_is_available(): void
    {
        if (config('database.default') !== 'mysql') {
            $this->markTestSkipped('Default database is not mysql.');
        }

        $pdo = DB::connection()->getPdo();
        $this->assertInstanceOf(\PDO::class, $pdo);

        $result = DB::select('select 1 as ok');
        $this->assertSame(1, (int) ($result[0]->ok ?? 0));
    }
}
