<?php

namespace Bpocallaghan\Sluggable\Tests;

use File;
use Illuminate\Foundation\Application;
use Illuminate\Database\Schema\Blueprint;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /** @var TestModel */
    protected $testModel;

    public function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase($this->app);
    }

    /**
     * @param Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $this->initializeDirectory($this->getTempDirectory());

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => $this->getTempDirectory().'/database.sqlite',
            'prefix' => '',
        ]);
    }

    /**
     * @param  $app
     */
    protected function setUpDatabase(Application $app)
    {
        file_put_contents($this->getTempDirectory().'/database.sqlite', null);

        $app['db']->connection()->getSchemaBuilder()->create('test_models', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->string('other_field')->nullable();
            $table->timestamps();
        });

        $app['db']->connection()->getSchemaBuilder()->create('test_model_soft_deletes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->string('other_field')->nullable();
            $table->softDeletes();
        });
    }

    protected function initializeDirectory(string $directory)
    {
        if (File::isDirectory($directory)) {
            File::deleteDirectory($directory);
        }

        File::makeDirectory($directory);
    }

    public function getTempDirectory() : string
    {
        return __DIR__.'/temp';
    }
}
