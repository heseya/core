<?php

namespace Heseya\Data\Contracts;

interface CoerceableEnum
{
    public static function coerce(int|string $valueOrName): ?static;
}
