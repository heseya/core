<?php

declare(strict_types=1);

namespace Support\LaravelData\Casts;

use App\Enums\Traits\EnumTrait;
use BackedEnum;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Casts\Uncastable;
use Spatie\LaravelData\Exceptions\CannotCastEnum;
use Spatie\LaravelData\Support\DataProperty;

/** @property class-string<\BackedEnum>&EnumTrait> $type */
final class EnumCoerceCast implements Cast
{
    /**
     * @param class-string<\BackedEnum>&EnumTrait> $type
     */
    public function __construct(
        protected ?string $type = null,
    ) {}

    public function cast(DataProperty $property, mixed $value, array $context): BackedEnum|Uncastable
    {
        $type = $this->type ?? $property->type->findAcceptedTypeForBaseType(BackedEnum::class);

        if ($type === null) {
            return Uncastable::create();
        }

        if (!in_array(EnumTrait::class, class_uses_recursive($type), true)) {
            throw CannotCastEnum::create($type, $value);
        }

        // @var class-string<\BackedEnum>&EnumTrait $type
        return $type::coerce($value) ?? throw CannotCastEnum::create($type, $value);
    }
}
