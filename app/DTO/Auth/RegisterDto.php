<?php

namespace App\DTO\Auth;

use App\Rules\ConsentExists;
use App\Rules\IsRegistrationRole;
use App\Rules\RequiredConsents;
use App\Utils\Map;
use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Attributes\Validation\BeforeOrEqual;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class RegisterDto extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        #[BeforeOrEqual('now')]
        public Optional|string $birthday_date,
        #[Rule('phone:AUTO')]
        public Optional|string $phone,
        public array|Optional $metadata_personal,

        public array $consents = [],
        public array $roles = [],
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
                \Illuminate\Validation\Rule::unique('users')->whereNull('deleted_at'),
            ],
            'password' => ['required', 'string', Password::defaults()],
            'consents.*' => [new ConsentExists(), 'boolean'],
            'consents' => ['array', new RequiredConsents()],
            'roles.*' => [new IsRegistrationRole()],
        ];
    }
}
