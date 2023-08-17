<?php

namespace Heseya\Data\RuleInferrers;

use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Present;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\RuleInferrers\RuleInferrer;
use Spatie\LaravelData\Support\DataProperty;
use Spatie\LaravelData\Support\Validation\PropertyRules;
use Spatie\LaravelData\Support\Validation\RequiringRule;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class RequiredRuleInferrer implements RuleInferrer
{
    public function handle(
        DataProperty $property,
        PropertyRules $rules,
        ValidationContext $context,
    ): PropertyRules {
        if ($this->shouldAddRule($property, $rules)) {
            $rules->prepend(new Required());
        }

        return $rules;
    }

    protected function shouldAddRule(DataProperty $property, PropertyRules $rules): bool
    {
        if ($property->type->isNullable || $property->type->isOptional) {
            return false;
        }

        if ($property->type->isDataCollectable && $rules->hasType(Present::class)) {
            return false;
        }

        if ($rules->hasType(Nullable::class)) {
            return false;
        }

        return !$rules->hasType(RequiringRule::class);
    }
}