<?php

namespace Database\Factories;

use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Page::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $name = $this->faker->sentence(rand(2, 4));

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'public' => $this->faker->boolean,
            'content_html' => '<p>' . $this->faker->sentence(rand(20, 40)) . '</p>',
        ];
    }
}
