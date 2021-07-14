<?php

namespace Database\Factories;

use App\Models\PasswordReset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => Hash::make('secret'),
            'remember_token' => Str::random(10),
        ];
    }

    public function withPasswordReset(string $email = null): ?object
    {
        if (null === $email) {
            return null;
        }

        $user = User::whereEmail($email)->first();
        $passwordReset = PasswordReset::create([
              'email' => $email,
              'token' => Password::createToken($user),
          ]);

        return $this->state([
            'email' => $user->email,
            'token' => $passwordReset->token,
        ]);
    }
}
