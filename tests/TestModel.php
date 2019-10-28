<?php

namespace Bpocallaghan\Sluggable\Tests;

use Bpocallaghan\Sluggable\HasSlug;
use Bpocallaghan\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    use HasSlug;

    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = false;

    /**
     * Get the options for generating the slug.
     */
    public function getSlugOptions() : SlugOptions
    {
        return $this->slugOptions ?? $this->getDefaultSlugOptions();
    }

    /**
     * Set the options for generating the slug.
     * @param SlugOptions $slugOptions
     * @return TestModel
     */
    public function setSlugOptions(SlugOptions $slugOptions) : self
    {
        $this->slugOptions = $slugOptions;

        return $this;
    }

    /**
     * Get the default slug options used in the tests.
     */
    public function getDefaultSlugOptions() : SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugFrom('name')
            ->saveSlugTo('slug');
    }
}
