<?php

namespace Tests;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Staudenmeir\LaravelUpsert\DatabaseServiceProvider;

class QueryTest extends TestCase
{
    public function testUpsert()
    {
        $now = now();
        DB::table('stats')->upsert(
            [
                ['post_id' => 1, 'date' => $now->toDateString(), 'views' => 1, 'was_updated' => false, 'created_at' => $now, 'updated_at' => $now],
                ['post_id' => 2, 'date' => $now->toDateString(), 'views' => 1, 'was_updated' => false, 'created_at' => $now, 'updated_at' => $now],
            ],
            ['post_id', 'date'],
            ['views' => DB::raw('stats.views + 1'), 'was_updated' => true, 'updated_at']
        );

        $stats = DB::table('stats')->orderBy('post_id')->get();
        $this->assertEquals([1, 2, 3], $stats->pluck('post_id')->all());
        $this->assertEquals([2, 1, 5], $stats->pluck('views')->all());
        $this->assertEquals([1, 0, 1], $stats->pluck('was_updated')->all());
        $this->assertNotEquals($stats[0]->created_at, $stats[0]->updated_at);
        $this->assertEquals($stats[1]->created_at, $stats[1]->updated_at);
    }

    public function testUpsertWithEmptyValues()
    {
        DB::enableQueryLog();

        $affected = DB::table('users')->upsert([], 'name');

        $this->assertEquals(0, $affected);
        $this->assertEmpty(DB::getQueryLog());
    }

    public function testUpsertWithoutColumns()
    {
        DB::table('users')->upsert(['name' => 'foo', 'active' => true], 'name');

        $users = DB::table('users')->get();
        $this->assertEquals(['foo'], $users->pluck('name')->all());
        $this->assertEquals([1], $users->pluck('active')->all());
    }

    public function testUpsertWithEmptyColumns()
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessageMatches('/unique/i');

        DB::table('users')->upsert(['name' => 'foo', 'active' => true], 'name', []);
    }

    public function testInsertIgnore()
    {
        $affected = DB::table('users')->insertIgnore([
            ['name' => 'foo', 'active' => true],
            ['name' => 'bar', 'active' => true],
        ], 'name');

        $this->assertEquals(1, $affected);
        $users = DB::table('users')->get();
        $this->assertEquals(['foo', 'bar'], $users->pluck('name')->all());
        $this->assertEquals([0, 1], $users->pluck('active')->all());
    }

    public function testInsertIgnoreWithEmptyValues()
    {
        DB::enableQueryLog();

        $affected = DB::table('users')->insertIgnore([]);

        $this->assertEquals(0, $affected);
        $this->assertEmpty(DB::getQueryLog());
    }

    protected function getPackageProviders($app)
    {
        return [DatabaseServiceProvider::class];
    }
}
