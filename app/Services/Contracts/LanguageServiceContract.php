<?php

namespace App\Services\Contracts;

use App\Dtos\LanguageDto;
use App\Models\Language;

interface LanguageServiceContract
{
    public function create(LanguageDto $dto): Language;

    public function update(Language $language, LanguageDto $dto): Language;

    public function delete(Language $language): void;

    public function setDefault(Language $language): void;
}
