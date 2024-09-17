<?php

declare(strict_types=1);

namespace Domain\ProductSchema\Services;

use App\Exceptions\PublishingException;
use App\Models\Option;
use App\Services\Contracts\AvailabilityServiceContract;
use App\Services\Contracts\MetadataServiceContract;
use App\Services\Contracts\TranslationServiceContract;
use Domain\ProductSchema\Dtos\SchemaCreateDto;
use Domain\ProductSchema\Dtos\SchemaDto;
use Domain\ProductSchema\Models\Schema;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;

final readonly class SchemaCrudService
{
    public function __construct(
        private AvailabilityServiceContract $availabilityService,
        private MetadataServiceContract $metadataService,
        private OptionService $optionService,
        private TranslationServiceContract $translationService,
    ) {}

    /**
     * @throws PublishingException
     */
    public function store(SchemaDto $dto): Schema
    {
        $schema = new Schema();

        return $this->update($schema, $dto);
    }

    /**
     * @throws PublishingException
     */
    public function update(Schema $schema, SchemaDto $dto): Schema
    {
        $schema->fill($dto->toArray());

        if (is_array($dto->translations)) {
            foreach ($dto->translations as $lang => $translations) {
                $schema->setLocale($lang)->fill($translations);
            }
        }

        if (!$schema->exists) {
            $this->translationService->checkPublished($schema, ['name']);
        }

        $schema->save();

        if ($dto->options instanceof DataCollection) {
            $this->optionService->sync($schema, $dto->options->items());
            $schema->refresh();
        }

        if ($schema->wasRecentlyCreated) {
            $this->translationService->checkPublishedRelations($schema, ['options' => ['name']]);
        } else {
            $this->translationService->checkPublished($schema, ['name']);
        }

        if (!$dto->used_schemas instanceof Optional) {
            $schema->usedSchemas()->detach();
        }

        if (is_array($dto->used_schemas)) {
            foreach ($dto->used_schemas as $input) {
                $used_schema = Schema::query()->findOrFail($input);
                $schema->usedSchemas()->attach($used_schema);
            }
        }

        if ($dto instanceof SchemaCreateDto && !($dto->metadata_computed instanceof Optional)) {
            $this->metadataService->sync($schema, $dto->metadata_computed);
        }

        $schema->options->each(
            fn (Option $option) => $this->availabilityService->calculateOptionAvailability($option),
        );

        $this->availabilityService->calculateSchemaAvailability($schema);

        return $schema;
    }

    public function destroy(Schema $schema): void
    {
        $schema->delete();
    }
}
