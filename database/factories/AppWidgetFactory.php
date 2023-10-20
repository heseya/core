<?php

namespace Database\Factories;

use Domain\App\Models\AppWidget;

class AppWidgetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AppWidget::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $name = $this->faker->name;

        return [
            'url' => $this->faker->unique()->url,
            'name' => $name,
            'section' => $this->faker->unique()->word,
        ];
    }
}
