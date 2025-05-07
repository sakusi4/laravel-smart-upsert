<?php
namespace Sakusi4\LaravelSmartUpsert\Tests;

use Orchestra\Testbench\TestCase as Base;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends Base
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'test');
        $app['config']->set('database.connections.test', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function setUpDatabase(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('email')->unique();
            $t->string('name');
            $t->timestamps();
        });
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
    }
}