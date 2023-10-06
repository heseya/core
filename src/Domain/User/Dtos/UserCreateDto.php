<?php

declare(strict_types=1);

namespace Domain\User\Dtos;

use App\Enums\RoleType;
use App\Traits\DtoHasPhone;
use Domain\Metadata\Dtos\MetadataPersonalDto;
use Domain\Metadata\Dtos\MetadataUpdateDto;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rule as ValidationRule;
use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\BeforeOrEqual;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;
use Support\Utils\Map;

final class UserCreateDto extends Data
{
    use DtoHasPhone;

    #[Computed]
    public Optional|string|null $phone_country;
    #[Computed]
    public Optional|string|null $phone_number;
    /**
     * @var array|Optional|MetadataPersonalDto[]|MetadataUpdateDto[]
     */
    #[Computed]
    #[MapOutputName('metadata')]
    public readonly array|Optional $metadata_computed;

    /**
     * @param string $name
     * @param string $email
     * @param string $password
     * @param string[]|Optional $roles
     * @param Optional|string $birthday_date
     * @param array<string, string>|Optional $metadata_public
     * @param array<string, string>|Optional $metadata_private
     * @param array<string, string>|Optional $metadata_personal
     */
    public function __construct(
        #[Required, StringType, Max(255)]
        public string $name,
        #[Required, StringType, Email, Max(255)]
        public string $email,
        #[Required, StringType, Url, Max(255)]
        public string $email_verify_url,
        #[Required, StringType, Max(255)]
        public string $password,
        #[ArrayType]
        public array|Optional $roles,
        #[Date, BeforeOrEqual('now')]
        public Optional|string $birthday_date,
        public Optional|string $phone,
        #[MapInputName('metadata')]
        public readonly array|Optional $metadata_public,
        public readonly array|Optional $metadata_private,
        public readonly array|Optional $metadata_personal,
    ) {
        $this->initializePhone();

        $this->metadata_computed = Map::toUserMetadata(
            $this->metadata_public,
            $this->metadata_private,
            $this->metadata_personal,
        );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'email' => [
                'required',
                'email',
                'max:255',
                ValidationRule::unique('users')->whereNull('deleted_at'),
            ],
            'email_verify_url' => [
                'required',
                'url',
                'max:255',
                'required_with:email',
            ],
            'password' => [Password::defaults()],
            'roles.*' => [
                'uuid',
                Rule::exists('roles', 'id')->where(
                    fn (Builder $query) => $query->whereNotIn(
                        'type',
                        [RoleType::AUTHENTICATED->value, RoleType::UNAUTHENTICATED->value],
                    ),
                ),
            ],
            'phone' => ['phone:AUTO'],
        ];
    }
}
