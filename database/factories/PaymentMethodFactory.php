<?php

namespace Database\Factories;

use Domain\PaymentMethods\Models\PaymentMethod;
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
            'icon' => 'https://picsum.photos/seed/' . mt_rand(0, 999999) . '/800',
            'url' => $this->faker->url,
        ];
    }

    public function allMethods(): static
    {
        return $this->forEachSequence([
            'name' => 'Przelewy24',
            'alias' =>  Str::slug('Przelewy24')
        ], [
            'name' => 'Bluemedia',
            'alias' =>  Str::slug('Bluemedia')
        ], [
            'name' => 'PayNow',
            'alias' =>  Str::slug('PayNow')
        ]);
    }
}
