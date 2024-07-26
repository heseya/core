<?php

namespace Database\Factories;

use App\Enums\SchemaType;
use Domain\ProductSchema\Models\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class SchemaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Schema::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'description' => $this->faker->sentence(10),
            'hidden' => mt_rand(0, 10) === 0,
            'required' => $this->faker->boolean,
            'default' => null,
            'published' => [App::getLocale()],
            'type' => SchemaType::SELECT,
        ];
    }

    public function create($attributes = [], ?Model $parent = null)
    {
        $result = parent::create($attributes, $parent);

        return $result->count() > 1 ? $result : $result->first();
    }
}
