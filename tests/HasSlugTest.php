<?php

namespace Bpocallaghan\Sluggable\Tests;

use Illuminate\Support\Str;
use Bpocallaghan\Sluggable\SlugOptions;

class HasSlugTest extends TestCase
{
    /** @test */
    public function save_a_slug_when_saving_a_model()
    {
        $model = TestModel::create(['name' => 'laravel-is-awesome']);

        $this->assertEquals('laravel-is-awesome', $model->slug);
    }

    /** @test */
    public function slug_can_be_null()
    {
        $model = TestModel::create(['name' => null]);

        $this->assertEquals('', $model->slug);

        $model = TestModel::create(['name' => null]);

        $this->assertEquals('-1', $model->slug);
    }

    /** @test */
    public function slug_will_not_change_if_source_did_not_change()
    {
        $model = TestModel::create(['name' => 'this is a test']);

        $model->other_field = 'Something Else';
        $model->save();

        $this->assertEquals('this-is-a-test', $model->slug);
    }

    public function update_slug_when_source_changed()
    {
        $model = TestModel::create(['name' => 'this is a test']);

        $model->name = 'Update name';
        $model->save();

        $this->assertEquals('update-name', $model->url);
    }

    /** @test */
    public function save_a_unique_slug_by_default()
    {
        TestModel::create(['name' => 'this is a test']);

        foreach (range(1, 10) as $i) {
            $model = TestModel::create(['name' => 'this is a test']);
            $this->assertEquals("this-is-a-test-{$i}", $model->slug);
        }
    }

    /** @test */
    public function it_will_use_separator_option_for_slug_generation()
    {
        $model = new class extends TestModel {
            public function getSlugOptions(): SlugOptions
            {
                return parent::getSlugOptions()->slugSeperator('_');
            }
        };

        $model->name = 'this is a test';
        $model->save();

        $this->assertEquals('this_is_a_test', $model->slug);
    }

    /** @test */
    public function save_a_unique_slug_when_using_soft_deletes()
    {
        TestModelSoftDeletes::create(['name' => 'this is a test', 'deleted_at' => date('Y-m-d h:i:s')]);

        foreach (range(1, 10) as $i) {
            $model = TestModelSoftDeletes::create(['name' => 'this is a test']);
            $this->assertEquals("this-is-a-test-{$i}", $model->slug);
        }
    }
}
