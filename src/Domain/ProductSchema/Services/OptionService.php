<?php

declare(strict_types=1);

namespace Domain\ProductSchema\Services;

use App\Events\OptionCreated;
use App\Models\Option;
use App\Services\Contracts\MetadataServiceContract;
use Domain\PriceMap\PriceMapService;
use Domain\ProductSchema\Dtos\OptionDto;
use Domain\ProductSchema\Models\Schema;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;

final readonly class OptionService
{
    public function __construct(
        private MetadataServiceContract $metadataService,
        private PriceMapService $priceMapService,
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

            if (!$optionItem->translations instanceof Optional) {
                foreach ($optionItem->translations as $lang => $translations) {
                    $option->setLocale($lang)->fill($translations);
                }
            }

            $option->save();

            if (!($optionItem->items instanceof Optional)) {
                $option->items()->sync($optionItem->items);
            }

            if (!($optionItem->metadata_computed instanceof Optional)) {
                $this->metadataService->sync($option, $optionItem->metadata_computed);
            }

            if ($optionItem->prices instanceof DataCollection) {
                $this->priceMapService->updateOptionPricesForDefaultMaps($option, $optionItem->prices);
            }

            $keep[] = $option->getKey();

            if ($option->wasRecentlyCreated) {
                OptionCreated::dispatch($option);
            }
        }

        $schema->options()->whereNotIn('id', $keep)->delete();
    }
}
