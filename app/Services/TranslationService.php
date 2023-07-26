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
        foreach ($model->published ?? [] as $lang) {
            foreach ($requiredKeys as $key) {
                if (!$model->hasTranslation($key, $lang)) {
                    throw new PublishingException("Model doesn't have all required translations to be published in {$lang}");
                }
            }
        }
    }

    /**
     * @throws PublishingException
     */
    public function checkPublishedRelations(Translatable $model, array $requiredRelationKeys): void
    {
        foreach ($model->published ?? [] as $lang) {
            foreach ($requiredRelationKeys as $relation => $keys) {
                // @phpstan-ignore-next-line
                $model->loadMissing($relation);
                // @phpstan-ignore-next-line
                foreach ($model->getRelation($relation) as $relatedModel) {
                    foreach ($keys as $key) {
                        if ($relatedModel->hasTranslation($key, $lang)) {
                            continue;
                        }

                        throw new PublishingException("Relation {$relation} doesn't have all required translations to be published in {$lang}");
                    }
                }
            }
        }
    }
}
