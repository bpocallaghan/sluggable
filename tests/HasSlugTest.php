<?php

namespace Bpocallaghan\Sluggable\Tests;

use Illuminate\Support\Str;
use Bpocallaghan\Sluggable\SlugOptions;
use PHPUnit\Framework\Attributes\Test;

class HasSlugTest extends TestCase
{
    #[Test]
    public function save_a_slug_when_saving_a_model()
    {
        $model = TestModel::create(['name' => 'laravel-is-awesome']);

        $this->assertEquals('laravel-is-awesome', $model->slug);
    }

    #[Test]
    public function slug_can_be_null()
    {
        $model = TestModel::create(['name' => null]);

        $this->assertEquals('', $model->slug);

        $model = TestModel::create(['name' => null]);

        $this->assertEquals('-1', $model->slug);
    }

    #[Test]
    public function slug_will_not_change_if_source_did_not_change()
    {
        $model = TestModel::create(['name' => 'this is a test']);

        $model->other_field = 'Something Else';
        $model->save();

        $this->assertEquals('this-is-a-test', $model->slug);
    }

    #[Test]
    public function update_slug_when_source_changed()
    {
        $model = TestModel::create(['name' => 'this is a test']);

        $model->name = 'Update name';
        $model->save();
        $model->refresh();

        $this->assertEquals('update-name', $model->slug);
    }

    #[Test]
    public function save_a_unique_slug_by_default()
    {
        TestModel::create(['name' => 'this is a test']);

        foreach (range(1, 10) as $i) {
            $model = TestModel::create(['name' => 'this is a test']);
            $this->assertEquals("this-is-a-test-{$i}", $model->slug);
        }
    }

    #[Test]
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

    #[Test]
    public function save_a_unique_slug_when_using_soft_deletes()
    {
        TestModelSoftDeletes::create(['name' => 'this is a test', 'deleted_at' => date('Y-m-d h:i:s')]);

        foreach (range(1, 10) as $i) {
            $model = TestModelSoftDeletes::create(['name' => 'this is a test']);
            $this->assertEquals("this-is-a-test-{$i}", $model->slug);
        }
    }

    #[Test]
    public function it_can_generate_non_unique_slugs()
    {
        $model = new class extends TestModel {
            public function getSlugOptions(): SlugOptions
            {
                return parent::getSlugOptions()->makeSlugUnique(false);
            }
        };

        $model->name = 'duplicate name';
        $model->save();

        $model2 = new class extends TestModel {
            public function getSlugOptions(): SlugOptions
            {
                return parent::getSlugOptions()->makeSlugUnique(false);
            }
        };

        $model2->name = 'duplicate name';
        $model2->save();

        $this->assertEquals('duplicate-name', $model->slug);
        $this->assertEquals('duplicate-name', $model2->slug);
    }

    #[Test]
    public function it_can_use_custom_slug_field_name()
    {
        $model = new class extends TestModel {
            public function getSlugOptions(): SlugOptions
            {
                return parent::getSlugOptions()->saveSlugTo('url');
            }
        };

        $model->name = 'test name';
        $model->save();

        $this->assertEquals('test-name', $model->url);
        $this->assertNull($model->slug);
    }

    #[Test]
    public function it_can_generate_slug_from_multiple_fields()
    {
        $model = new class extends TestModel {
            protected $fillable = ['name', 'other_field'];

            public function getSlugOptions(): SlugOptions
            {
                return parent::getSlugOptions()->generateSlugFrom(['name', 'other_field']);
            }
        };

        $model->name = 'first';
        $model->other_field = 'second';
        $model->save();

        $this->assertEquals('first-second', $model->slug);
    }

    #[Test]
    public function it_can_generate_slug_from_callable()
    {
        $model = new class extends TestModel {
            public function getSlugOptions(): SlugOptions
            {
                return parent::getSlugOptions()->generateSlugFrom(function ($model) {
                    return strtoupper($model->name);
                });
            }
        };

        $model->name = 'test name';
        $model->save();

        // Str::slug() converts to lowercase, so even though callable returns uppercase,
        // the final slug will be lowercase
        $this->assertEquals('test-name', $model->slug);
    }

    #[Test]
    public function it_respects_maximum_length()
    {
        $model = new class extends TestModel {
            public function getSlugOptions(): SlugOptions
            {
                return parent::getSlugOptions()->maximumLength(10);
            }
        };

        $model->name = 'this is a very long name that should be truncated';
        $model->save();

        $this->assertLessThanOrEqual(10, strlen($model->slug));
    }

    #[Test]
    public function it_can_disable_slug_generation_on_create()
    {
        $model = new class extends TestModel {
            public function getSlugOptions(): SlugOptions
            {
                return parent::getSlugOptions()->generateSlugOnCreate(false);
            }
        };

        $model->name = 'test name';
        $model->save();

        $this->assertNull($model->slug);
    }

    #[Test]
    public function it_can_disable_slug_generation_on_update()
    {
        $model = TestModel::create(['name' => 'original name']);
        $originalSlug = $model->slug;

        $model->setSlugOptions(
            $model->getSlugOptions()->generateSlugOnUpdate(false)
        );

        $model->name = 'updated name';
        $model->save();

        $this->assertEquals($originalSlug, $model->slug);
    }

    #[Test]
    public function it_regenerates_slug_when_source_changes_and_new_slug_is_taken()
    {
        // Create first model
        $model1 = TestModel::create(['name' => 'test name']);
        $this->assertEquals('test-name', $model1->slug);

        // Create second model with different name
        $model2 = TestModel::create(['name' => 'different name']);
        $this->assertEquals('different-name', $model2->slug);

        // Update second model to have same name as first
        $model2->name = 'test name';
        $model2->save();

        // Should get unique slug
        $this->assertEquals('test-name-1', $model2->slug);
    }

    #[Test]
    public function it_keeps_existing_slug_when_source_unchanged_and_slug_still_unique()
    {
        $model = TestModel::create(['name' => 'unique name']);
        $originalSlug = $model->slug;

        // Update non-source field
        $model->other_field = 'something';
        $model->save();

        // Slug should remain the same
        $this->assertEquals($originalSlug, $model->slug);
    }

    #[Test]
    public function it_handles_empty_string_source()
    {
        $model = TestModel::create(['name' => '']);

        $this->assertEquals('', $model->slug);

        $model2 = TestModel::create(['name' => '']);

        $this->assertEquals('-1', $model2->slug);
    }

    #[Test]
    public function it_handles_special_characters_in_source()
    {
        $model = TestModel::create(['name' => 'Test & Name (2024)']);

        $this->assertEquals('test-name-2024', $model->slug);
    }

    #[Test]
    public function it_handles_unicode_characters()
    {
        $model = TestModel::create(['name' => 'Café & Résumé']);

        $this->assertEquals('cafe-resume', $model->slug);
    }

    #[Test]
    public function it_can_manually_generate_slug()
    {
        $model = new class extends TestModel {
            public function getSlugOptions(): SlugOptions
            {
                return parent::getSlugOptions()->generateSlugOnCreate(false);
            }
        };

        $model->name = 'test name';
        $model->save();

        $this->assertNull($model->slug);

        $model->generateSlug();
        $model->save();

        $this->assertEquals('test-name', $model->slug);
    }

    #[Test]
    public function it_handles_updating_model_with_existing_unique_slug()
    {
        $model1 = TestModel::create(['name' => 'test name']);
        $model2 = TestModel::create(['name' => 'other name']);

        // Both should have unique slugs
        $this->assertEquals('test-name', $model1->slug);
        $this->assertEquals('other-name', $model2->slug);

        // Update model1 with non-source field - slug should stay the same
        $model1->other_field = 'updated';
        $model1->save();

        $this->assertEquals('test-name', $model1->slug);
    }
}
