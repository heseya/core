<?php

declare(strict_types=1);

namespace Domain\PriceMap\Resources;

use App\Models\Option;
use Domain\PriceMap\PriceMapSchemaOptionPrice;
use Domain\ProductSchema\Models\Schema;
use Illuminate\Support\Facades\App;
use Support\Dtos\DataWithGlobalMetadata;

final class PriceMapPricesForProductPartialSchemaOptionData extends DataWithGlobalMetadata
{
    public function __construct(
        public string $schema_option_id,
        public string $schema_option_price,
        public string|null $schema_id = null,
        public string|null $schema_name = null,
        public string|null $schema_option_name = null,
    ) {}

    public static function fromModel(PriceMapSchemaOptionPrice $price): static
    {
        $instance = new self($price->option_id, (string) $price->value);

        assert($price->option instanceof Option);

        return $instance->setSchemaOption($price->option);
    }

    public function setSchemaOption(Option $option): self
    {
        $this->schema_option_name = $option->getTranslation('name', App::currentLocale());

        assert($option->schema instanceof Schema);

        return $this->setSchema($option->schema);
    }

    public function setSchema(Schema $schema): self
    {
        $this->schema_id = $schema->id;
        $this->schema_name = $schema->name;

        return $this;
    }
}
