<?php

namespace Domain\Consent\Dtos;

use App\Rules\Translations;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class ConsentCreateDto extends Data
{
    /**
     * @param array<string, array<string, string>> $translations
     */
    public function __construct(
        #[Rule(new Translations(['name', 'description_html']))]
        public readonly array $translations,
        public readonly bool $required,
    ) {}
}
