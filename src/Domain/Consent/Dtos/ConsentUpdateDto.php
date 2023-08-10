<?php

namespace Domain\Consent\Dtos;

use App\Rules\Translations;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class ConsentUpdateDto extends Data
{
    /**
     * @param array<string, array<string, string>>|Optional $translations
     */
    public function __construct(
        #[Uuid]
        public readonly string|Optional $id,
        #[Rule(new Translations(['name', 'description_html']))]
        public readonly array|Optional $translations,
        public readonly bool|Optional $required,
    ) {}
}
