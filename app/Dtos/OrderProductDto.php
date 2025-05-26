<?php

namespace App\Dtos;

use Heseya\Dto\Dto;
use Heseya\Dto\Missing;

class OrderProductDto extends Dto
{
    private string $product_id;
    private float $quantity;
    private array $schemas;
    private array $discounts;
    private Missing|string|null $related_product_id;

    public static function fromArray(array $array): self
    {
        $schemas = $array['schemas'] ?? [];
        if (count($schemas) > 0) {
            foreach ($schemas as $schema => $value) {
                $schemas[$schema] = $value ?? '';
            }
        }

        return new self(
            product_id: $array['product_id'],
            quantity: $array['quantity'],
            schemas: $schemas,
            discounts: [],
            related_product_id: array_key_exists('related_product_id', $array) ? $array['related_product_id'] : new Missing(),
        );
    }

    public function getProductId(): string
    {
        return $this->product_id;
    }

    public function getQuantity(): float
    {
        return $this->quantity;
    }

    public function getSchemas(): array
    {
        return $this->schemas;
    }

    public function getDiscounts(): array
    {
        return $this->discounts;
    }

    public function addDiscount(string $id): void
    {
        $this->discounts[] = $id;
    }

    public function getRelatedProductId(): Missing|string|null
    {
        return $this->related_product_id;
    }
}
