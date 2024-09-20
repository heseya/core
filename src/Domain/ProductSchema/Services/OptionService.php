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
        $default_option = null;

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

            if (($option->wasRecentlyCreated || $option->wasChanged('default')) && $option->default) {
                $default_option = $option;
            }

            if (!($optionItem->items instanceof Optional)) {
                $option->items()->sync($optionItem->items);
            }

            if (!($optionItem->metadata_computed instanceof Optional)) {
                $this->metadataService->sync($option, $optionItem->metadata_computed);
            }

            if ($optionItem->prices instanceof DataCollection) {
                $this->priceMapService->updateOptionPricesForDefaultMaps($option, $optionItem->prices, is_bool($optionItem->default) && $optionItem->default);
            }

            $keep[] = $option->getKey();

            if ($option->wasRecentlyCreated) {
                OptionCreated::dispatch($option);
            }
        }

        $schema->options()->whereNotIn('id', $keep)->delete();

        if ($default_option === null) {
            $schema->refresh();
            $default_option = $schema->required
                ? $schema->options->where('default', '=', true)->first(null, $schema->options->first())
                : $schema->options->where('default', '=', true)->first();
        }

        if ($default_option === null) {
            // Schema has no options or is not required, otherwise it would never be null
            $schema->required = false;
            $schema->saveQuietly();
        } else {
            $default_option->default = true;
            $default_option->saveQuietly();
            $schema->options()->where('id', '!=', $default_option->getKey())->update(['default' => false]);
        }
    }
}
