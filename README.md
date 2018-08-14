# Generate slugs when saving Laravel Eloquent models

Provides a HasSlug trait that will generate a unique slug when saving your Laravel Eloquent model. 

The slugs are generated with Laravel `str_slug` method, whereby spaces are converted to '-'.

```php
$model = new EloquentModel();
$model->name = 'laravel is awesome';
$model->save();

echo $model->slug; // ouputs "laravel-is-awesome"
```

## Installation

Update your project's `composer.json` file.

```bash
composer require bpocallaghan/sluggable
```

## Usage

Your Eloquent models can use the `Bpocallaghan\Sluggable\HasSlug` trait and the `Bpocallaghan\Sluggable\SlugOptions` class.

The trait has a protected method `getSlugOptions()` that you can implement for customization. 

Here's an example:

```php
class YourEloquentModel extends Model
{
    use HasSlug;
    
    /**
     * This function is optional and only required
     * when you want to override the default behaviour
     */
    protected function getSlugOptions()
    {
        return SlugOptions::create()
            ->slugSeperator('-')
            ->generateSlugFrom('name')
            ->saveSlugTo('slug');
    }
}
```

If you want to generate your slug from a relationship.

```php
class YourEloquentModel extends Model
{
    use HasSlug;
    
    public function getNameAndFooAttribute()
    {
        $name = $this->name;
        if ($this->foo) {
            $name .= " {$this->foo->name}";
        }

        return $name;
    }
    
    protected function getSlugOptions()
    {
        return SlugOptions::create()
            ->generateSlugFrom('name_and_foo');
    }
}
```

## Config

You do not have to add the method in you model (the above will be used as default). It is only needed when you want to change the default behaviour.

By default it will generate a slug from the `name` and save to the `slug` column.

It will suffix a `-1` to make the slug unique. You can disable it by calling `makeSlugUnique(false)`.

It will use the `-` as a separator. You can change this by calling `slugSeperator('_')`.

You can use multiple fields as the source of the slug `generateSlugFrom(['firstname', 'lastname'])`.

You can also pass a `callable` function to `generateSlugFrom()`.

Have a look [here for the options](https://github.com/bpocallaghan/sluggable/blob/master/src/SlugOptions.php) and available config functions.

## Change log

Please see the [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

#### Demonstration
See it in action at a [Laravel Admin Starter](https://github.com/bpocallaghan/laravel-admin-starter) project.