<?php

namespace App\Schemas;

use App\Http\Resources\Resource;
use Illuminate\Queue\SerializesModels;

/**
 * @OA\Schema()
 */
abstract class Schema
{
    use SerializesModels;

    private string $name;

    private float $price;

    public function validate(): bool
    {
        return true;
    }

    public function toResource(): Resource
    {
        $resource = '\\App\\Http\\Resources\\Schemas\\' . class_basename($this) . 'Resource';

        return new $resource($this);
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * @param float $price
     */
    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
