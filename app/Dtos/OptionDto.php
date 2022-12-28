<?php

namespace App\Dtos;

use App\Traits\MapMetadata;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;

class OptionDto extends Dto
{
    use MapMetadata;

    protected string|Missing $id;
    protected string|Missing $name;
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

    public function getPrice(): Missing|float
    {
        return $this->price;
    }

    public function isDisabled(): Missing|bool
    {
        return $this->disabled;
    }

    public function getItems(): Missing|array
    {
        return $this->items;
    }

    private static function valueOrMissing(array $array, string $key): mixed
    {
        return array_key_exists($key, $array) ? $array[$key] : new Missing();
    }
}
