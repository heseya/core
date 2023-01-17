<?php

namespace App\Services;

use App\Models\Option;
use App\Models\Schema;
use App\Services\Contracts\MetadataServiceContract;
use App\Services\Contracts\OptionServiceContract;
use Heseya\Dto\Missing;
use Illuminate\Support\Collection;

class OptionService implements OptionServiceContract
{
    public function __construct(
        private MetadataServiceContract $metadataService,
    ) {
    }

    public function sync(Schema $schema, array $options = []): void
    {
        $keep = Collection::empty();

        foreach ($options as $order => $optionItem) {
            $optionData = array_merge(
                $optionItem->toArray(),
                ['order' => $order],
            );

            if (!$optionItem->getId() instanceof Missing) {
                /** @var Option $option */
                $option = Option::query()->findOrFail($optionItem->getId());
                $option->update($optionData);
            } else {
                $option = $schema->options()->create($optionData);
            }

            $option->items()->sync(
                !$optionItem->getItems() instanceof Missing ?
                    $optionItem->getItems() ?? []
                    : [],
            );

            if (!($optionItem->getMetadata() instanceof Missing)) {
                $this->metadataService->sync($option, $optionItem->getMetadata());
            }

            $keep->add($option->getKey());
        }

        $schema->options()->whereNotIn('id', $keep)->delete();
    }
}
