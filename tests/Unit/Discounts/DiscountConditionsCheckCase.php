<?php

namespace Tests\Unit\Discounts;

use App\Models\ConditionGroup;
use App\Services\Contracts\DiscountServiceContract;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Domain\Currency\Currency;
use Heseya\Dto\DtoException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

abstract class DiscountConditionsCheckCase extends TestCase
{
    use RefreshDatabase;

    protected DiscountServiceContract $discountService;
    protected ConditionGroup $conditionGroup;
    protected Currency $currency;

    /**
     * @throws DtoException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->conditionGroup = ConditionGroup::create();

        $this->currency = Currency::DEFAULT;

        $this->discountService = App::make(DiscountServiceContract::class);
    }
}
