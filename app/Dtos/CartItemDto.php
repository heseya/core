<?php

namespace App\Dtos;

use Heseya\Dto\Dto;
use Heseya\Dto\Missing;

final class CartItemDto extends Dto
{
    private string $cartitem_id;
    private string $product_id;
    private float $quantity;
    private array $schemas;
    private array $discounts;
    private Missing|string|null $related_product_id;

    public static function fromArray(array $array): self
    {
        return new self(
            cartitem_id: $array['cartitem_id'],
            product_id: $array['product_id'],
            quantity: $array['quantity'],
            schemas: array_key_exists('schemas', $array) ? $array['schemas'] : [],
            discounts: [],
            related_product_id: array_key_exists('related_product_id', $array) && $array['related_product_id'] !== null ? $array['related_product_id'] : new Missing(),
        );
    }

    public function getCartItemId(): string
    {
        return $this->cartitem_id;
    }

    public function getProductId(): string
    {
        return $this->product_id;
    }

    public function getQuantity(): float
    {
        return $this->quantity;
    }

    public function setQuantity(float $quantity): void
    {
        $this->quantity = $quantity;
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
