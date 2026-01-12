<?php

namespace Bpocallaghan\Sluggable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasSlug
{
    /** @var \Bpocallaghan\Sluggable\SlugOptions */
    protected $slugOptions;

    /**
     * Get the options for generating the slug.
     */
    protected function getSlugOptions()
    {
        return SlugOptions::create();
    }

    /**
     * Boot the trait.
     */
    protected static function bootHasSlug()
    {
        static::creating(function (Model $model) {
            $model->generateSlugOnCreate();
        });

        static::updating(function (Model $model) {
            $model->generateSlugOnUpdate();
        });
    }

    /**
     * Generate a slug on create
     */
    protected function generateSlugOnCreate()
    {
        $this->slugOptions = $this->getSlugOptions();

        if (! $this->slugOptions->generateSlugOnCreate) {
            return;
        }

        $this->createSlug();
    }

    /**
     * Handle adding slug on model update.
     */
    protected function generateSlugOnUpdate()
    {
        $this->slugOptions = $this->getSlugOptions();

        if (! $this->slugOptions->generateSlugOnUpdate) {
            return;
        }

        // Check if any of the source fields used to generate the slug have changed
        $sourceFieldsChanged = $this->hasSlugSourceChanged();

        // If source fields changed, always regenerate the slug
        if ($sourceFieldsChanged) {
            $this->createSlug();

            return;
        }

        // Otherwise, check if current slug is still valid
        $slugCurrent = $this->attributes[$this->slugOptions->slugField];
        $slugUpdate = $this->checkUpdatingSlug($slugCurrent);
        // no need to update slug (slug is still unique)
        if ($slugUpdate !== false) {
            return;
        }

        $this->createSlug();
    }

    /**
     * Check if any of the source fields used to generate the slug have changed.
     *
     * @return bool
     */
    protected function hasSlugSourceChanged()
    {
        $sourceFields = is_array($this->slugOptions->generateSlugFrom)
            ? $this->slugOptions->generateSlugFrom
            : [$this->slugOptions->generateSlugFrom];

        // If it's a callable, we can't easily check if it changed, so always regenerate
        if (is_callable($this->slugOptions->generateSlugFrom)) {
            return true;
        }

        // Check if any of the source fields are dirty
        foreach ($sourceFields as $field) {
            if ($this->isDirty($field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle setting slug on explicit request.
     */
    public function generateSlug()
    {
        $this->slugOptions = $this->getSlugOptions();

        $this->createSlug();
    }

    /**
     * Add the slug to the model.
     */
    protected function createSlug()
    {
        $slug = $this->generateNonUniqueSlug();

        if ($this->slugOptions->generateUniqueSlug) {
            $slug = $this->makeSlugUnique($slug);
        }

        $this->attributes[$this->slugOptions->slugField] = $slug;
    }

    /**
     * Generate a non unique slug for this record.
     */
    protected function generateNonUniqueSlug()
    {
        $slug = $this->getSlugSourceString();

        return Str::slug($slug, $this->slugOptions->slugSeparator);
    }

    /**
     * Get the string that should be used as base for the slug.
     */
    protected function getSlugSourceString()
    {
        // if callback given
        if (is_callable($this->slugOptions->generateSlugFrom)) {
            $slug = call_user_func($this->slugOptions->generateSlugFrom, $this);

            return substr($slug, 0, $this->slugOptions->maximumLength);
        }

        // concatenate on the fields and implode on seperator
        $slug = collect($this->slugOptions->generateSlugFrom)->map(function ($fieldName = '') {
            return $this->$fieldName;
        })->implode($this->slugOptions->slugSeparator);

        return substr($slug, 0, $this->slugOptions->maximumLength);
    }

    /**
     * Make the slug unique with suffix
     *
     * @return string
     */
    protected function makeSlugUnique($slug)
    {
        $i = 1;
        $slugIsUnique = false;

        // get existing slugs (1 db query)
        $list = $this->getExistingSlugs($slug);

        // slug is already unique
        if ($list->count() === 0) {
            return $slug;
        }

        // collection to array
        if (! is_array($list)) {
            $list = $list->toArray();
        }

        // loop through the list and add suffix
        while (! $slugIsUnique) {
            $uniqueSlug = $slug.$this->slugOptions->slugSeparator.($i++);
            if (! in_array($uniqueSlug, $list)) {
                $slugIsUnique = true;
            }
        }

        return $uniqueSlug;
    }

    /**
     * Get existing slugs matching slug
     *
     * @return \Illuminate\Support\Collection|static
     */
    protected function getExistingSlugs($slug)
    {
        $query = static::where($this->slugOptions->slugField, 'LIKE', "{$slug}%")
            ->withoutGlobalScopes(); // ignore scopes

        // Only include trashed records if the model uses SoftDeletes
        if ($this->usesSoftDeletes()) {
            $query->withTrashed(); // trashed, when entry gets activated again
        }

        return $query->orderBy($this->slugOptions->slugField)
            ->get()
            ->pluck($this->slugOptions->slugField);
    }

    /**
     * Check if the model uses SoftDeletes trait
     *
     * @return bool
     */
    protected function usesSoftDeletes()
    {
        return in_array(
            'Illuminate\Database\Eloquent\SoftDeletes',
            class_uses_recursive(static::class)
        );
    }

    /**
     * Check if we are updating
     * Find entries with same slug
     * Exlude current model's entry
     *
     * @return bool
     */
    private function checkUpdatingSlug($slug)
    {
        if ($this->id >= 1) {
            // find entries matching slug, exclude updating entry
            $exist = self::where($this->slugOptions->slugField, $slug)
                ->where('id', '!=', $this->id)
                ->first();

            // no entries, save to use current slug
            if (! $exist) {
                return $slug;
            }
        }

        // unique slug needed
        return false;
    }
}
