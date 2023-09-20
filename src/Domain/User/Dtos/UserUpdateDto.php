<?php

declare(strict_types=1);

namespace Domain\User\Dtos;

use App\Enums\RoleType;
use App\Models\User;
use App\Traits\DtoHasPhone;
use App\Traits\MapMetadata;
use Domain\Metadata\Dtos\MetadataUpdateDto;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\BeforeOrEqual;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;
use Support\Utils\Map;

final class UserUpdateDto extends Data
{
    use DtoHasPhone;
    use MapMetadata;

    #[Computed]
    public string|Optional|null $phone_country;
    #[Computed]
    public string|Optional|null $phone_number;
    /**
     * @var Optional|MetadataUpdateDto[]
     */
    #[Computed]
    #[MapOutputName('metadata')]
    public readonly array|Optional $metadata_computed;

    /**
     * @param Optional|string|null $name
     * @param Optional|string|null $email
     * @param string[]|Optional $roles
     * @param Optional|string|null $birthday_date
     * @param Optional|string|null $phone
     * @param array|Optional $metadata_public
     * @param array|Optional $metadata_private
     * @param array|Optional $metadata_personal
     */
    public function __construct(
        #[Nullable, Required, StringType, Max(255)]
        public Optional|string|null $name,
        public Optional|string|null $email,
        #[ArrayType]
        public array|Optional $roles,
        #[Nullable, Date, StringType, BeforeOrEqual('now')]
        public Optional|string|null $birthday_date,
        #[Nullable, StringType]
        public Optional|string|null $phone,
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
     * @return array<string, array<int,mixed>>
     */
    public static function rules(ValidationContext $context): array
    {
        /** @var User $user */
        $user = request()->route('user');

        return [
            'email' => [
                'email',
                'max:255',
                Rule::unique('users')
                    ->ignoreModel($user)
                    ->whereNull('deleted_at'),
            ],
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
