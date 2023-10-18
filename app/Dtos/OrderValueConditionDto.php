<?php

namespace App\Dtos;

use App\Models\Price;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Heseya\Dto\Missing;

class OrderValueConditionDto extends ConditionDto
{
    /** @var PriceDto[]|Missing */
    private array|Missing $min_values;
    /** @var PriceDto[]|Missing */
    private array|Missing $max_values;
    private bool $include_taxes;
    private bool $is_in_range;

    public static function fromArray(array $array): self
    {
        $min_values = match (true) {
            !array_key_exists('min_values', $array),
            !is_array($array['min_values']),
            empty($array['min_values']) => new Missing(),
            default => array_map(fn (array|Price|PriceDto $min) => $min instanceof PriceDto ? $min : PriceDto::from($min), $array['min_values']),
        };

        $max_values = match (true) {
            !array_key_exists('max_values', $array),
            !is_array($array['max_values']),
            empty($array['max_values']) => new Missing(),
            default => array_map(fn (array|Price|PriceDto $max) => $max instanceof PriceDto ? $max : PriceDto::from($max), $array['max_values']),
        };

        return new self(
            type: $array['type'],
            min_values: $min_values,
            max_values: $max_values,
            include_taxes: $array['include_taxes'],
            is_in_range: $array['is_in_range'],
        );
    }

    public function getMinValues(): array|Missing
    {
        return $this->min_values;
    }

    public function getMinValueForCurrency(Currency|string $currency): PriceDto|null
    {
        if (is_string($currency)) {
            $currency = Currency::from($currency);
        }

        if ($this->getMinValues() instanceof Missing) {
            return null;
        }

        return collect($this->getMinValues())->first(fn (PriceDto $value) => $value->currency === $currency);
    }

    public function getMaxValues(): array|Missing
    {
        return $this->max_values;
    }

    public function getMaxValueForCurrency(Currency|string $currency): PriceDto|null
    {
        if (is_string($currency)) {
            $currency = Currency::from($currency);
        }

        if ($this->getMaxValues() instanceof Missing) {
            return null;
        }

        return collect($this->getMaxValues())->first(fn (PriceDto $value) => $value->currency === $currency);
    }

    public function isIncludeTaxes(): bool
    {
        return $this->include_taxes;
    }

    public function isIsInRange(): bool
    {
        return $this->is_in_range;
    }
}
