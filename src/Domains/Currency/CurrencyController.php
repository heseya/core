<?php

declare(strict_types=1);

namespace Domains\Currency;

use App\Http\Controllers\Controller;
use Brick\Money\Exception\UnknownCurrencyException;
use Domains\Support\ResourceDto;

class CurrencyController extends Controller
{
    /**
     * @throws UnknownCurrencyException
     */
    public function index(): ResourceDto
    {
        $currencies = array_map(function (Currency $currency) {
            $currency = \Brick\Money\Currency::of($currency->value);

            return new CurrencyDto(
                $currency->getName(),
                $currency->getCurrencyCode(),
                $currency->getDefaultFractionDigits(),
            );
        }, Currency::cases());

        return new ResourceDto($currencies);
    }
}
