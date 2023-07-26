<?php

namespace App\Services\Contracts;

use App\DTO\Language\LanguageCreateDto;
use App\DTO\Language\LanguageUpdateDto;
use App\Models\Language;

interface LanguageServiceContract
{
    public function create(LanguageCreateDto $dto): Language;

    public function update(Language $language, LanguageUpdateDto $dto): Language;

    public function delete(Language $language): void;
}
