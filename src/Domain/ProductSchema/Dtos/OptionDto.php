<?php

declare(strict_types=1);

namespace Domain\ProductSchema\Dtos;

use App\Rules\Price;
use App\Rules\PricesEveryCurrency;
use App\Rules\Translations;
use Brick\Math\BigDecimal;
use Domain\Metadata\Dtos\MetadataUpdateDto;
use Domain\Price\Dtos\PriceDto;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;
use Support\Utils\Map;

/**
 * @property MetadataUpdateDto[]|Optional $metadata_computed
 */
final class OptionDto extends Data
{
    /**
     * @var MetadataUpdateDto[]|Optional $metadata_computed
     */
    #[Computed]
    #[MapOutputName('metadata')]
    public array|Optional $metadata_computed;

    /**
     * @param array<string,string[]> $translations
     * @param DataCollection<int,PriceDto> $prices
     * @param string[]|Optional $items
     * @param string[]|Optional $metadata_public
     * @param string[]|Optional $metadata_private
     */
    public function __construct(
        #[Uuid]
        public readonly Optional|string $id,
        #[Rule(new Translations(['name']))]
        public readonly array|Optional $translations,
        #[DataCollectionOf(PriceDto::class)]
        public DataCollection|Optional $prices,
        public readonly bool|Optional $disabled,
        public readonly array|Optional $items,

        #[MapInputName('metadata')]
        public readonly array|Optional $metadata_public,
        public readonly array|Optional $metadata_private,
    ) {
        $this->metadata_computed = Map::toMetadata(
            $this->metadata_public,
            $this->metadata_private,
        );
    }

    /**
     * @return array<string,array<int, mixed>>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'items.*' => ['uuid', 'exists:items,id'],
            'prices' => ['required', new PricesEveryCurrency()],
            'prices.*' => ['sometimes', new Price(['value'], min: BigDecimal::zero())],
        ];
    }
}
