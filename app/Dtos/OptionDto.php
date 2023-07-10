<?php

namespace App\Dtos;

use App\Traits\MapMetadata;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;

class OptionDto extends Dto
{
    use MapMetadata;

    protected Missing|string $id;
    protected Missing|string $name;
    protected float|Missing $price;
    protected bool|Missing $disabled;
    protected array|Missing $items;

    protected array|Missing $metadata;

    public static function fromArray(array $array): self
    {
        return new self(
            id: self::valueOrMissing($array, 'id'),
            name: self::valueOrMissing($array, 'name'),
            price: self::valueOrMissing($array, 'price'),
            disabled: self::valueOrMissing($array, 'disabled'),
            items: self::valueOrMissing($array, 'items'),
            metadata: self::mapMetadataFromArray($array),
        );
    }

    public function getId(): Missing|string
    {
        return $this->id;
    }

    public function getName(): Missing|string
    {
        return $this->name;
    }

    public function getPrice(): float|Missing
    {
        return $this->price;
    }

    public function isDisabled(): bool|Missing
    {
        return $this->disabled;
    }

    public function getItems(): array|Missing
    {
        return $this->items;
    }

    private static function valueOrMissing(array $array, string $key): mixed
    {
        return array_key_exists($key, $array) ? $array[$key] : new Missing();
    }
}
