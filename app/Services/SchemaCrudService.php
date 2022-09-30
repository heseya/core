<?php

namespace App\Services;

use App\Dtos\SchemaDto;
use App\Models\Option;
use App\Models\Product;
use App\Models\Schema;
use App\Services\Contracts\AvailabilityServiceContract;
use App\Services\Contracts\MetadataServiceContract;
use App\Services\Contracts\OptionServiceContract;
use App\Services\Contracts\ProductServiceContract;
use App\Services\Contracts\SchemaCrudServiceContract;
use Heseya\Dto\Missing;

class SchemaCrudService implements SchemaCrudServiceContract
{
    public function __construct(
        private AvailabilityServiceContract $availabilityService,
        private MetadataServiceContract $metadataService,
        private OptionServiceContract $optionService,
        private ProductServiceContract $productService,
    ) {
    }

    public function store(SchemaDto $dto): Schema
    {
        /** @var Schema $schema */
        $schema = Schema::query()->create($dto->toArray());

        if (!$dto->getOptions() instanceof Missing && $dto->getOptions() !== null) {
            $this->optionService->sync($schema, $dto->getOptions());
            $schema->refresh();
        }

        if (!$dto->getUsedSchemas() instanceof Missing && $dto->getUsedSchemas() !== null) {
            foreach ($dto->getUsedSchemas() as $input) {
                $used_schema = Schema::query()->findOrFail($input);

                $schema->usedSchemas()->attach($used_schema);
            }

            $schema->refresh();
        }

        if (!($dto->getMetadata() instanceof Missing)) {
            $this->metadataService->sync($schema, $dto->getMetadata());
        }

        $schema->options->each(
            fn (Option $option) => $this->availabilityService->calculateOptionAvailability($option),
        );
        $this->availabilityService->calculateSchemaAvailability($schema);

        return $schema;
    }

    public function update(Schema $schema, SchemaDto $dto): Schema
    {
        $schema->update($dto->toArray());
        if (!$dto->getOptions() instanceof Missing) {
            $this->optionService->sync($schema, $dto->getOptions());
            $schema->refresh();
        }

        if (!$dto->getUsedSchemas() instanceof Missing) {
            $schema->usedSchemas()->detach();

            foreach ($dto->getUsedSchemas() as $input) {
                $used_schema = Schema::query()->findOrFail($input);

                $schema->usedSchemas()->attach($used_schema);
            }
        }

        if (!($dto->getMetadata() instanceof Missing)) {
            $this->metadataService->sync($schema, $dto->getMetadata());
        }

        $schema->products->each(
            fn (Product $product) => $this->productService->updateMinMaxPrices($product),
        );
        $schema->options->each(
            fn (Option $option) => $this->availabilityService->calculateOptionAvailability($option),
        );
        $this->availabilityService->calculateSchemaAvailability($schema);

        return $schema;
    }

    public function destroy(Schema $schema): void
    {
        $products = $schema->products;
        $schema->delete();

        $products->each(
            fn (Product $product) => $this->productService->updateMinMaxPrices($product),
        );
    }
}
