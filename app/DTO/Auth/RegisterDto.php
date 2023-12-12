<?php

namespace App\DTO\Auth;

use App\Rules\ConsentsExists;
use App\Rules\IsRegistrationRole;
use App\Rules\OrganizationTokenEmail;
use App\Rules\RequiredConsents;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule as ValidationRule;
use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Attributes\Validation\BeforeOrEqual;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Url;
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
        #[Required, StringType, Url, Max(255)]
        public readonly string $email_verify_url,

        public readonly Optional|string $organization_token,
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
                ValidationRule::unique('users')->whereNull('deleted_at'),
                new OrganizationTokenEmail(),
            ],
            'password' => ['required', 'string', Password::defaults()],
            'consents' => ['array', new RequiredConsents()],
            'consents.*' => ['boolean', new ConsentsExists()],
            'roles.*' => [new IsRegistrationRole()],
            'organization_token' => [
                ValidationRule::exists('organization_tokens', 'token')->where(fn ($query) => $query->where('expires_at', '>', Carbon::now())),
            ],
        ];
    }
}
