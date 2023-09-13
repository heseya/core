<?php

namespace Domain\User\Dtos;

use App\Models\User;
use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class TFAPasswordDto extends Data
{
    public User $user;

    public function __construct(
        #[Required, StringType, Max(255)]
        public string $password,
    ) {
        $user = request()->user();
    }

    public static function rules(ValidationContext $context): array
    {
        return ['password' => [Password::defaults()]];
    }
}
