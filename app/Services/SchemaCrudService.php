<?php

namespace App\Services;

use App\Dtos\SchemaDto;
use App\Models\Product;
use App\Models\Schema;
use App\Services\Contracts\MetadataServiceContract;
use App\Services\Contracts\OptionServiceContract;
use App\Services\Contracts\ProductServiceContract;
use App\Services\Contracts\SchemaCrudServiceContract;
use Heseya\Dto\Missing;

class SchemaCrudService implements SchemaCrudServiceContract
{
    public function __construct(
        private ProductServiceContract $productService,
        private OptionServiceContract $optionService,
        private MetadataServiceContract $metadataService
    ) {
    }

    public function store(SchemaDto $dto): Schema
    {
        $schema = Schema::create($dto->toArray());

        if (!$dto->getOptions() instanceof Missing) {
            $this->optionService->sync($schema, $dto->getOptions());
            $schema->refresh();
        }

        if (!$dto->getUsedSchemas() instanceof Missing) {
            foreach ($dto->getUsedSchemas() as $input) {
                $used_schema = Schema::findOrFail($input);

                $schema->usedSchemas()->attach($used_schema);
            }

            $schema->refresh();
        }

        if (!($dto->getMetadata() instanceof Missing)) {
            $this->metadataService->sync($schema, $dto->getMetadata());
        }

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
                $used_schema = Schema::findOrFail($input);

                $schema->usedSchemas()->attach($used_schema);
            }
        }

        if (!($dto->getMetadata() instanceof Missing)) {
            $this->metadataService->sync($schema, $dto->getMetadata());
        }

        $schema->products->each(
            fn (Product $product) => $this->productService->updateMinMaxPrices($product),
        );

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
