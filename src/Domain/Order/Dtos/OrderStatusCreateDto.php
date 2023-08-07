<?php

declare(strict_types=1);

namespace Domain\Order\Dtos;

use App\Rules\Translations;
use Domain\Metadata\Dtos\MetadataUpdateDto;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Support\Utils\Map;

final class OrderStatusCreateDto extends Data
{
    /** @var Optional|MetadataUpdateDto[] */
    #[Computed]
    public readonly array|Optional $metadata;

    /**
     * @param array<string, array<string, string>> $translations
     * @param string[] $published
     * @param string[]|Optional $metadata_public
     * @param string[]|Optional $metadata_private
     */
    public function __construct(
        #[Rule(new Translations(['name', 'description']))]
        public readonly array $translations,
        #[Max(6)]
        public readonly string $color,
        public readonly bool $cancel,
        public readonly bool $hidden,
        public readonly bool $no_notifications,
        #[Min(1)]
        public readonly array $published,

        #[MapInputName('metadata')]
        public readonly array|Optional $metadata_public,
        public readonly array|Optional $metadata_private,
    ) {
        $this->metadata = Map::toMetadata(
            $this->metadata_public,
            $this->metadata_private,
        );
    }

    /**
     * @return array<string, string[]>
     */
    public static function rules(): array
    {
        return [
            'translations.*.name' => ['required', 'string', 'max:60'],
            'translations.*.description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
