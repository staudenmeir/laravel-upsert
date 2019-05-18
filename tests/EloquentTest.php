<?php

namespace Tests;

use Illuminate\Support\Facades\DB;
use Tests\Models\User;

class EloquentTest extends TestCase
{
    public function testUpsert()
    {
        User::upsert([
            ['name' => 'foo', 'active' => true],
            ['name' => 'bar', 'active' => true],
        ], 'name', ['active']);

        $users = User::all();
        $this->assertEquals(['foo', 'bar'], $users->pluck('name')->all());
        $this->assertEquals([1, 1], $users->pluck('active')->all());
        $this->assertNotEquals($users[0]->created_at, $users[0]->updated_at);
        $this->assertNotNull($users[1]->created_at);
        $this->assertEquals($users[1]->created_at, $users[1]->updated_at);
    }

    public function testUpsertWithEmptyValues()
    {
        DB::enableQueryLog();

        $affected = User::upsert([], 'name');

        $this->assertEquals(0, $affected);
        $this->assertEmpty(DB::getQueryLog());
    }

    public function testUpsertWithoutColumns()
    {
        User::upsert(['name' => 'foo', 'active' => true], 'name');

        $users = User::all();
        $this->assertEquals(['foo'], $users->pluck('name')->all());
        $this->assertEquals([1], $users->pluck('active')->all());
    }

    public function testUpsertWithoutTimestamps()
    {
        $user = new class extends User {
            public $timestamps = false;
        };

        $user::upsert([
            ['name' => 'foo', 'active' => true],
            ['name' => 'bar', 'active' => true],
        ], 'name');

        $users = User::all();
        $this->assertEquals($users[0]->created_at, $users[0]->updated_at);
        $this->assertNull($users[1]->created_at);
        $this->assertNull($users[1]->updated_at);
    }

    public function testUpsertWithoutTimestamp()
    {
        $user = new class extends User {
            const UPDATED_AT = null;
        };

        $user::upsert([
            ['name' => 'foo', 'active' => true],
            ['name' => 'bar', 'active' => true],
        ], 'name');

        $users = User::all();
        $this->assertEquals($users[0]->created_at, $users[0]->updated_at);
        $this->assertNotNull($users[1]->created_at);
        $this->assertNull($users[1]->updated_at);
    }

    public function testUpsertWithUpdatedAtValue()
    {
        User::upsert(['name' => 'foo', 'active' => true], 'name', ['updated_at' => null]);

        $user = User::first();
        $this->assertNull($user->updated_at);
    }

    public function testUpsertWithDuplicateUpdatedAtColumn()
    {
        User::upsert(['name' => 'foo', 'active' => true], 'name', ['updated_at']);

        $user = User::first();
        $this->assertNotEquals($user->created_at, $user->updated_at);
    }

    public function testInsertIgnore()
    {
        $affected = User::insertIgnore([
            ['name' => 'foo', 'active' => true],
            ['name' => 'bar', 'active' => true],
        ], 'name');

        $this->assertEquals(1, $affected);
        $users = User::all();
        $this->assertEquals(['foo', 'bar'], $users->pluck('name')->all());
        $this->assertEquals([0, 1], $users->pluck('active')->all());
        $this->assertNotNull($users[1]->created_at);
        $this->assertNotNull($users[1]->updated_at);
    }

    public function testInsertIgnoreWithEmptyValues()
    {
        DB::enableQueryLog();

        $affected = User::insertIgnore([]);

        $this->assertEquals(0, $affected);
        $this->assertEmpty(DB::getQueryLog());
    }

    public function testInsertIgnoreWithoutTimestamps()
    {
        $user = new class extends User {
            public $timestamps = false;
        };

        $user::insertIgnore(['name' => 'bar', 'active' => true], 'name');

        $users = User::all();
        $this->assertNull($users[1]->created_at);
        $this->assertNull($users[1]->updated_at);
    }

    public function testInsertIgnoreWithoutTimestamp()
    {
        $user = new class extends User {
            const UPDATED_AT = null;
        };

        $user::insertIgnore(['name' => 'bar', 'active' => true], 'name');

        $users = User::all();
        $this->assertNotNull($users[1]->created_at);
        $this->assertNull($users[1]->updated_at);
    }
}
