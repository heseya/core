<?php

declare(strict_types=1);

namespace Domain\User\Dtos;

use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class PasswordResetSaveDto extends Data
{
    public function __construct(
        #[Required, StringType]
        public string $token,

        #[Required, Email, Exists('users', 'email')]
        public string $email,

        #[Required, StringType, Max(255)]
        public string $password,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(ValidationContext $context): array
    {
        return ['password' => [Password::defaults()]];
    }
}
