<?php

namespace App\DTO\SeoMetadata;

use App\Rules\ClassWithSeo;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

class ExcludedModelDto extends Data
{
    public function __construct(
        #[Uuid]
        public readonly string $id,
        #[Rule(new ClassWithSeo())]
        public readonly string $model,
    ) {}
}
