<?php

declare(strict_types=1);

namespace Domain\User\Dtos;

use App\Enums\RoleType;
use App\Traits\DtoHasPhone;
use App\Traits\MapMetadata;
use Heseya\Dto\Missing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\BeforeOrEqual;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class UserCreateDto extends Data
{
    use DtoHasPhone;
    use MapMetadata;

    #[Computed]
    public string|Optional|null $phone_country;
    #[Computed]
    public string|Optional|null $phone_number;
    /** @var array<string,string>|Missing */
    #[Computed]
    public array|Missing $metadata;

    /**
     * @param string $name
     * @param string $email
     * @param string $password
     * @param string[]|Optional $roles
     * @param Optional|string $birthday_date
     */
    public function __construct(
        #[Required, StringType, Max(255)]
        public string $name,
        #[Required, Email, Max(255)]
        public string $email,
        #[Required, StringType, Max(255)]
        public string $password,
        #[ArrayType]
        public array|Optional $roles,
        #[Date, BeforeOrEqual('now')]
        public Optional|string $birthday_date,
        public Optional|string $phone,
    ) {
        $this->initializePhone();
        $this->metadata = self::mapMetadata(request());
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'email' => [Rule::unique('users')->whereNull('deleted_at')],
            'password' => [Password::defaults()],
            'roles.*' => [
                'uuid',
                Rule::exists('roles', 'id')->where(
                    fn (Builder $query) => $query->whereNotIn(
                        'type',
                        [RoleType::AUTHENTICATED->value, RoleType::UNAUTHENTICATED->value]
                    )
                ),
            ],
            'phone' => ['phone:AUTO'],
        ];
    }
}
