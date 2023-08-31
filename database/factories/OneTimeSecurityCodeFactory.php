<?php

namespace Database\Factories;

use App\Models\OneTimeSecurityCode;
use Illuminate\Support\Facades\Hash;
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
            'code' => Hash::make(Str::random(5) . '-' . Str::random(5)),
            'expires_at' => null,
        ];
    }
}
