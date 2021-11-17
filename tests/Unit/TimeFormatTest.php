<?php

namespace Tests\Unit;

use App\Models\Address;
use App\Models\App;
use App\Models\Audit;
use App\Models\Deposit;
use App\Models\Discount;
use App\Models\Item;
use App\Models\Media;
use App\Models\Option;
use App\Models\Order;
use App\Models\Schema;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class TimeFormatTest extends TestCase
{
    use RefreshDatabase;

    public function testAddressTimeFormat(): void
    {
        $this->modelTimeFormat(Address::factory()->create(), ['created_at', 'updated_at']);
    }

    public function testAppTimeFormat(): void
    {
        $this->modelTimeFormat(App::factory()->create(), ['created_at', 'updated_at']);
    }

    public function testAuditTimeFormat(): void
    {
        $audit = Audit::create([
            'event' => 'event',
            'auditable_type' => 'auditable_type',
            'auditable_id' => 'auditable_id',
        ]);

        $this->modelTimeFormat($audit, ['created_at']);
    }

    public function testDepositTimeFormat(): void
    {
        /** @var Item $item */
        $item = Item::factory()->create();
        $deposit = Deposit::factory()->create([
            'item_id' => $item->getKey(),
        ]);

        $this->modelTimeFormat($deposit, ['created_at', 'updated_at']);
    }

    public function testDiscountTimeFormat(): void
    {
        $discount = Discount::factory()->create([
            'starts_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addHour(),
        ]);
        $discount->delete();

        $this->modelTimeFormat($discount, [
            'created_at',
            'updated_at',
            'deleted_at',
            'starts_at',
            'expires_at',
        ]);
    }

    public function testItemTimeFormat(): void
    {
        /** @var Item $item */
        $item = Item::factory()->create();
        $item->delete();

        $this->modelTimeFormat($item, ['created_at', 'updated_at', 'deleted_at']);
    }

    public function testMediaTimeFormat(): void
    {
        $this->modelTimeFormat(Media::factory()->create(), ['created_at', 'updated_at']);
    }

    public function testOptionTimeFormat(): void
    {
        /** @var Schema $schema */
        $schema = Schema::factory()->create();
        $options = Option::factory()->create([
            'schema_id' => $schema->getKey(),
        ]);

        $this->modelTimeFormat($options, ['created_at', 'updated_at']);
    }

    public function testOrderTimeFormat(): void
    {
        $this->modelTimeFormat(Order::factory()->create(), ['created_at', 'updated_at']);
    }

    public function modelTimeFormat($model, array $fields): void
    {
        $model->refresh();

        Collection::make($fields)->each(fn ($field) => [
            $this->assertInstanceOf(Carbon::class, $model->$field, "Field $field error:"),
            $this->assertEquals(
                $model->$field->toIso8601String(),
                $model->$field . '',
                "Field $field error:",
            ),
        ]);
    }
}
