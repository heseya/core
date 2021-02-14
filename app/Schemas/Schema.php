<?php

namespace App\Schemas;

use App\Http\Resources\Resource;
use App\Models\Model;
use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

abstract class Schema extends Model
{
    use SerializesModels;

    protected $fillable = [
        'name',
        'price',
        'validation',
    ];

    public function getTable(): string
    {
        return Str::of(class_basename($this))
            ->before('Schema')
            ->start('schemas')
            ->snake();
    }

    public function getResource(): string
    {
        return Str::of(class_basename($this))
            ->start('App\\Http\\Resources\\Schemas\\')
            ->finish('Resource');
    }

    public function toResource(): Resource
    {
        $resource = $this->getResource();

        return new $resource($this);
    }

    /* Base functions invented to be overloaded on final classes */

    /**
     * Check if user input is valid
     *
     * @param mixed $input
     *
     * @return bool
     */
    public function validate(array $input): bool
    {
        $validator = Validator::make($input, $this->getValidation());

        if ($validator->fails()) {
            return false;
        }

        return true;
    }

    /* Getters */

    public function getValidationAttribute($value): array
    {
        return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
    }

    /* Relations */

    public function products(): MorphToMany
    {
        return $this->morphedByMany(Product::class, 'product_schema');
    }
}
