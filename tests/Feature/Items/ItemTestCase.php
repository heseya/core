<?php

namespace Tests\Feature\Items;

use App\Models\Deposit;
use App\Models\Item;
use App\Services\SchemaCrudService;
use Domain\Currency\Currency;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

abstract class ItemTestCase extends TestCase
{
    protected Item $item;

    protected array $expected;

    protected Currency $currency = Currency::DEFAULT;

    public function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(null);

        $this->item = Item::factory()->create();

        Deposit::factory()->create([
            'item_id' => $this->item->getKey(),
        ]);

        $this->item->refresh();

        // Expected response
        $this->expected = [
            'id' => $this->item->getKey(),
            'name' => $this->item->name,
            'sku' => $this->item->sku,
            'quantity' => $this->item->quantity,
            'metadata' => [],
        ];
    }
}
