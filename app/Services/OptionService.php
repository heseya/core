<?php

namespace App\Services;

use App\DTO\ProductSchema\OptionDto;
use App\Models\Option;
use App\Models\Schema;
use App\Services\Contracts\MetadataServiceContract;
use App\Services\Contracts\OptionServiceContract;
use Spatie\LaravelData\Optional;

final readonly class OptionService implements OptionServiceContract
{
    public function __construct(
        private MetadataServiceContract $metadataService,
    ) {}

    /**
     * @param OptionDto[] $options
     */
    public function sync(Schema $schema, array $options): void
    {
        $keep = [];

        foreach ($options as $order => $optionItem) {
            $optionData = array_merge(
                $optionItem->toArray(),
                ['order' => $order],
            );

            if (!$optionItem->id instanceof Optional) {
                /** @var Option $option */
                $option = Option::query()->findOrFail($optionItem->id);
                $option->fill($optionData);
            } else {
                /** @var Option $option */
                $option = $schema->options()->make($optionData);
            }

            foreach ($optionItem->translations ?? [] as $lang => $translations) {
                $option->setLocale($lang)->fill($translations);
            }

            $option->save();

            if (!($optionItem->items instanceof Optional)) {
                $option->items()->sync($optionItem->items);
            }

            if (!($optionItem->metadata instanceof Optional)) {
                $this->metadataService->sync($option, $optionItem->metadata);
            }

            $keep[] = $option->getKey();
        }

        $schema->options()->whereNotIn('id', $keep)->delete();
    }
}
