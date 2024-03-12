<?php

namespace App\DTO\Auth;

use App\Rules\ConsentsExists;
use App\Rules\EmailUnique;
use App\Rules\IsRegistrationRole;
use App\Rules\RequiredConsents;
use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Attributes\Validation\BeforeOrEqual;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;
use Support\Utils\Map;

class RegisterDto extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        #[BeforeOrEqual('now')]
        public readonly Optional|string $birthday_date,
        #[Rule('phone:AUTO')]
        public readonly Optional|string $phone,
        public array|Optional $metadata_personal,

        public readonly array $consents = [],
        public readonly array $roles = [],
    ) {
        $this->metadata_personal = Map::toMetadataPersonal($this->metadata_personal);
    }

    public static function rules(ValidationContext $context): array
    {
        return [
            'email' => [
                'required',
                'email',
                'max:255',
                new EmailUnique(),
            ],
            'password' => ['required', 'string', Password::defaults()],
            'consents' => ['array', new RequiredConsents()],
            'consents.*' => ['boolean', new ConsentsExists()],
            'roles.*' => [new IsRegistrationRole()],
        ];
    }
}
