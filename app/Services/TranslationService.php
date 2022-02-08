<?php

namespace App\Services;

use App\Exceptions\PublishingException;
use App\Models\Interfaces\Translatable;
use App\Services\Contracts\TranslationServiceContract;

class TranslationService implements TranslationServiceContract
{
    /**
     * @throws PublishingException
     */
    public function checkPublished(Translatable $model, array $requiredKeys): void
    {
        foreach ($model->published as $lang) {
            foreach ($requiredKeys as $key) {
                if (!$model->hasTranslation($key, $lang)) {
                    throw new PublishingException(
                        "Model doesn't have all required translations to be published in $lang",
                    );
                }
            }
        }
    }
}
