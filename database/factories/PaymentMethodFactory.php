<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentMethodFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PaymentMethod::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $name = $this->faker->randomElement([
            'Przelewy24',
            'Bluemedia',
            'PayNow',
        ]);

        return [
            'name' => $name,
            'alias' => Str::slug($name),
            'public' => $this->faker->boolean,
            'icon' => 'https://picsum.photos/seed/' . rand(0, 999999) . '/800',
            'url' => $this->faker->url,
        ];
    }
}
