<?php

namespace Heseya\Data\RuleInferrers;

use BackedEnum;
use Heseya\Data\Contracts\CoerceableEnum;
use Heseya\Data\Validation\EnumValueOrKey;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\RuleInferrers\RuleInferrer;
use Spatie\LaravelData\Support\DataProperty;
use Spatie\LaravelData\Support\Validation\PropertyRules;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class BuiltInTypesRuleInferrer implements RuleInferrer
{
    public function handle(
        DataProperty $property,
        PropertyRules $rules,
        ValidationContext $context,
    ): PropertyRules {
        if ($property->type->acceptsType('int')) {
            $rules->add(new Numeric());
        }

        if ($property->type->acceptsType('string')) {
            $rules->add(new StringType());
        }

        if ($property->type->acceptsType('bool')) {
            $rules->add(new BooleanType());
        }

        if ($property->type->acceptsType('float')) {
            $rules->add(new Numeric());
        }

        if ($property->type->acceptsType('array')) {
            $rules->add(new ArrayType());
        }

        if ($enumClass = $property->type->findAcceptedTypeForBaseType(BackedEnum::class)) {
            if (is_a($enumClass, CoerceableEnum::class, true)) {
                $rules->add(new EnumValueOrKey($enumClass));
            } else {
                $rules->add(new Enum($enumClass));
            }
        }

        return $rules;
    }
}
