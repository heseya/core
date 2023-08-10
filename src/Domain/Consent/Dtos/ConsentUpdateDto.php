<?php

declare(strict_types=1);

namespace Domain\Consent\Dtos;

use App\Rules\Translations;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class ConsentUpdateDto extends Data
{
    /**
     * @param array<string, array<string, string>>|Optional $translations
     */
    public function __construct(
        #[Uuid]
        public readonly Optional|string $id,
        #[Rule(new Translations(['name', 'description_html']))]
        public readonly array|Optional $translations,
        public readonly bool|Optional $required,
    ) {}
}
