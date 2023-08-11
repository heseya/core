<?php

declare(strict_types=1);

namespace Domain\Consent\Dtos;

use App\Rules\Translations;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;

final class ConsentCreateDto extends Data
{
    /**
     * @param array<string, array<string, string>> $translations
     * @param string[] $published
     */
    public function __construct(
        #[Rule(new Translations(['name', 'description_html']))]
        public readonly array $translations,
        public readonly bool $required,
        public readonly array $published,
    ) {}
}
