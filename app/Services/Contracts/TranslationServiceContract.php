<?php

namespace App\Services\Contracts;

use App\Exceptions\PublishingException;
use App\Models\Interfaces\Translatable;

interface TranslationServiceContract
{
    /**
     * @throws PublishingException
     */
    public function checkPublished(Translatable $model, array $requiredKeys): void;
}
