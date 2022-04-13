<?php

namespace App\Dtos;

use Heseya\Dto\Dto;

class CartItemDto extends Dto
{
    private string $cartitem_id;
    private string $product_id;
    private float $quantity;
    private array $schemas;

    public static function fromArray(array $array): self
    {
        return new self(
            cartitem_id: $array['cartitem_id'],
            product_id: $array['product_id'],
            quantity: $array['quantity'],
            schemas: array_key_exists('schemas', $array) ? $array['schemas'] : [],
        );
    }

    public function getCartitemId(): string
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

    public function getSchemas(): array
    {
        return $this->schemas;
    }
}
