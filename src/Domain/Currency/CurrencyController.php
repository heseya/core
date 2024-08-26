<?php

declare(strict_types=1);

namespace Domain\Currency;

use App\Http\Controllers\Controller;
use Brick\Money\Exception\UnknownCurrencyException;
use Spatie\LaravelData\DataCollection;

final class CurrencyController extends Controller
{
    /**
     * @return DataCollection<int,CurrencyDto>
     *
     * @throws UnknownCurrencyException
     */
    public function index(): DataCollection
    {
        return CurrencyDto::collection(Currency::cases());
    }
}
