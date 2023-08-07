<?php

namespace Tests\Unit;

use App\Models\Address;
use App\Models\App;
use App\Models\Deposit;
use App\Models\Discount;
use App\Models\Item;
use App\Models\Media;
use App\Models\Option;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\OrderSchema;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Permission;
use App\Models\Price;
use App\Models\PriceRange;
use App\Models\Product;
use App\Models\Role;
use App\Models\Schema;
use App\Models\Setting;
use App\Models\ShippingMethod;
use App\Models\Status;
use App\Models\Tag;
use App\Models\Token;
use App\Models\User;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Page\Page;
use Domain\ProductSet\ProductSet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

    public function testDepositTimeFormat(): void
    {
        /** @var Item $item */
        $item = Item::factory()->create();
        $deposit = Deposit::factory()->create([
            'item_id' => $item->getKey(),
            'shipping_date' => Carbon::now()->startOfDay()->addDay(),
        ]);

        $this->modelTimeFormat($deposit, ['created_at', 'updated_at', 'shipping_date']);
    }

    public function testDiscountTimeFormat(): void
    {
        $discount = Discount::factory()->create();
        $discount->delete();

        $this->modelTimeFormat($discount, [
            'created_at',
            'updated_at',
            'deleted_at',
        ]);
    }

    public function testItemTimeFormat(): void
    {
        /** @var Item $item */
        $item = Item::factory()->create([
            'shipping_date' => Carbon::now()->startOfDay()->addDay(),
            'unlimited_stock_shipping_date' => Carbon::now()->addDay(),
        ]);
        $item->delete();

        $this->modelTimeFormat($item, [
            'created_at',
            'updated_at',
            'deleted_at',
            'shipping_date',
            'unlimited_stock_shipping_date',
        ]);
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

    public function testOrderProductTimeFormat(): void
    {
        /** @var Order $order */
        $order = Order::factory()->create();
        /** @var Product $product */
        $product = Product::factory()->create();
        $orderProduct = OrderProduct::factory()->create([
            'order_id' => $order->getKey(),
            'product_id' => $product->getKey(),
        ]);

        $this->modelTimeFormat($orderProduct, ['created_at', 'updated_at']);
    }

    public function testOrderSchemaTimeFormat(): void
    {
        /** @var Order $order */
        $order = Order::factory()->create();
        /** @var Product $product */
        $product = Product::factory()->create();
        /** @var OrderProduct $orderProduct */
        $orderProduct = OrderProduct::factory()->create([
            'order_id' => $order->getKey(),
            'product_id' => $product->getKey(),
        ]);
        $orderSchema = OrderSchema::factory()->create([
            'order_product_id' => $orderProduct->getKey(),
        ]);

        $this->modelTimeFormat($orderSchema, ['created_at', 'updated_at']);
    }

    public function testPageTimeFormat(): void
    {
        $this->modelTimeFormat(Page::factory()->create(), ['created_at', 'updated_at']);
    }

    public function testPaymentTimeFormat(): void
    {
        /** @var Order $order */
        $order = Order::factory()->create();
        $payment = Payment::factory()->create([
            'order_id' => $order->getKey(),
        ]);

        $this->modelTimeFormat($payment, ['created_at', 'updated_at']);
    }

    public function testPaymentMethodTimeFormat(): void
    {
        $this->modelTimeFormat(PaymentMethod::factory()->create(), ['created_at', 'updated_at']);
    }

    public function testPermissionTimeFormat(): void
    {
        $permission = Permission::create([
            'name' => 'name',
        ]);

        $this->modelTimeFormat($permission, ['created_at', 'updated_at']);
    }

    /**
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     * @throws NumberFormatException
     */
    public function testPriceTimeFormat(): void
    {
        $price = Price::query()->create([
            'model_id' => 'model_id',
            'model_type' => 'model_type',
            'price_type' => '',
            'value' => Money::of(10, Currency::DEFAULT->value),
        ]);

        $this->modelTimeFormat($price, ['created_at', 'updated_at']);
    }

    /**
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     */
    public function testPriceRangeTimeFormat(): void
    {
        $currency = Currency::DEFAULT->value;

        $priceRange = PriceRange::query()->create([
            'start' => Money::zero($currency),
            'value' => Money::of(10, $currency),
        ]);

        $this->modelTimeFormat($priceRange, ['created_at', 'updated_at']);
    }

    public function testProductTimeFormat(): void
    {
        $product = Product::factory()->create([
            'shipping_date' => Carbon::now()->startOfDay()->addDay(),
        ]);

        $this->modelTimeFormat($product, ['created_at', 'updated_at', 'shipping_date']);
    }

    public function testProductSetTimeFormat(): void
    {
        $this->modelTimeFormat(ProductSet::factory()->create(), ['created_at', 'updated_at']);
    }

    public function testRoleTimeFormat(): void
    {
        $permission = Role::create([
            'name' => 'name',
            'guard' => 'api',
        ]);

        $this->modelTimeFormat($permission, ['created_at', 'updated_at']);
    }

    public function testSchemaTimeFormat(): void
    {
        $this->modelTimeFormat(Schema::factory()->create(), ['created_at', 'updated_at']);
    }

    public function testSettingTimeFormat(): void
    {
        $permission = Setting::create([
            'name' => 'name',
            'value' => 'value',
            'public' => true,
        ]);

        $this->modelTimeFormat($permission, ['created_at', 'updated_at']);
    }

    public function testShippingMethodTimeFormat(): void
    {
        $this->modelTimeFormat(ShippingMethod::factory()->create(), ['created_at', 'updated_at']);
    }

    public function testStatusTimeFormat(): void
    {
        $this->modelTimeFormat(Status::factory()->create(), ['created_at', 'updated_at']);
    }

    public function testTagTimeFormat(): void
    {
        $this->modelTimeFormat(Tag::factory()->create(), ['created_at', 'updated_at']);
    }

    public function testTokenTimeFormat(): void
    {
        $token = Token::create([
            'expires_at' => Carbon::now()->addHour(),
        ]);

        $this->modelTimeFormat($token, ['created_at', 'updated_at', 'expires_at']);
    }

    public function testUserTimeFormat(): void
    {
        $this->modelTimeFormat(User::factory()->create(), ['created_at', 'updated_at']);
    }

    public function modelTimeFormat($model, array $fields): void
    {
        $model->refresh();

        Collection::make($fields)->each(fn ($field) => [
            $this->assertInstanceOf(Carbon::class, $model->{$field}, "Field {$field} error:"),
            $this->assertEquals(
                $model->{$field}->toIso8601String(),
                $model->{$field} . '',
                "Field {$field} error:",
            ),
        ]);
    }
}
