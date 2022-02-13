<?php

namespace App\Services;

use App\Exceptions\PublishingException;
use App\Models\Option;
use App\Models\Schema;
use App\Services\Contracts\OptionServiceContract;
use App\Services\Contracts\TranslationServiceContract;
use Illuminate\Support\Collection;

class OptionService implements OptionServiceContract
{
    public function __construct(
        protected TranslationServiceContract $translationService,
    ) {
    }

    /**
     * @throws PublishingException
     */
    public function sync(Schema $schema, array $options = []): void
    {
        $keep = Collection::empty();

        foreach ($options as $order => $input) {
            $input['order'] = $order;

            if (isset($input['id'])) {
                $option = Option::findOrFail($input['id']);
                $option->fill($input);
            } else {
                $option = $schema->options()->make($input);
            }

            foreach ($input['translations'] ?? [] as $lang => $translations) {
                $option->setLocale($lang)->fill($translations);
            }

            $option->save();

            $option->items()->sync($input['items'] ?? []);

            $keep->add($option->getKey());
        }

        $schema->options()->whereNotIn('id', $keep)->delete();
    }
}
