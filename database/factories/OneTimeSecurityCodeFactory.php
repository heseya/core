<?php

namespace Database\Factories;

use App\Models\OneTimeSecurityCode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class OneTimeSecurityCodeFactory extends Factory
{

    protected $model = OneTimeSecurityCode::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'code' => Crypt::encrypt(Str::random(5) . '-' . Str::random(5)),
            'expires_at' => null,
        ];
    }
}
