<?php

namespace Domain\User\Dtos;

use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class ChangePasswordDto extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public string $password,
        #[Required, StringType, Max(255)]
        public string $password_new,
    ) {
    }

    public static function rules(ValidationContext $context): array
    {
        return ['password_new' => [Password::defaults()]];
    }
}
