<?php

namespace Bpocallaghan\Sluggable\Tests;

use Illuminate\Support\Str;
use Bpocallaghan\Sluggable\SlugOptions;

class HasSlugTest extends TestCase
{
    /** @test */
    public function save_a_slug_when_saving_a_model()
    {
        $model = TestModel::create(['name' => 'Convert this into a slug']);

        $this->assertEquals('convert-this-into-a-slug', $model->slug);
    }
}
