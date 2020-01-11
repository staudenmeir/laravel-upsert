<?php

namespace Tests;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Base;
use Tests\Models\User;

abstract class TestCase extends Base
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->boolean('active');
            $table->timestamps();
        });

        Schema::create('stats', function (Blueprint $table) {
            $table->unsignedInteger('post_id');
            $table->date('date');
            $table->unsignedInteger('views');
            $table->boolean('was_updated');
            $table->timestamps();
            $table->unique(['post_id', 'date']);
        });

        Model::unguard();

        User::create(['name' => 'foo', 'active' => false]);

        Model::reguard();

        DB::table('stats')->insert([
            ['post_id' => 1, 'date' => now()->toDateString(), 'views' => 1, 'was_updated' => false, 'created_at' => now(), 'updated_at' => now()],
            ['post_id' => 3, 'date' => now()->toDateString(), 'views' => 5, 'was_updated' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Carbon::setTestNow(Carbon::now()->addSecond());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);

        parent::tearDown();
    }

    protected function getEnvironmentSetUp($app)
    {
        $config = require __DIR__.'/config/database.php';

        $app['config']->set('database.default', 'testing');

        $app['config']->set('database.connections.testing', $config[getenv('DATABASE') ?: 'sqlite']);
    }
}
