<?php

namespace Tests\Feature;

use App\Enums\SchemaType;
use App\Models\Item;
use App\Models\Option;
use App\Models\Product;
use App\Models\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private Item $item;
    private Option $option;
    private Schema $schema;
    private Product $product;

    public function setUp(): void
    {
        parent::setUp();
        $this->product = Product::factory()->create([
            'available' => 0,
        ]);

        $this->user->givePermissionTo('deposits.add');
    }

    public function testRestockAvailable()
    {
        $schema = Schema::factory()->create([
            'required' => 1,
            'type' => 4,
        ]);

        $this->product->schemas()->save($schema);

        $item = Item::factory()->create([
            'quantity' => 0,
        ]);

        $option = Option::factory()->create(['schema_id' => $schema->getKey()]);

        $item->options()->save($option);

        $this->actingAs($this->user)->postJson('/items/id:' . $item->getKey() . '/deposits', [
            'quantity' => 6,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'available' => true,
        ]);

        $this->assertTrue($item->options->every(fn ($option) => $option->available));
        $this->assertTrue($item->options->pluck('schema')->every(fn ($schema) => $schema->available));
        $this->assertTrue($this->product->refresh()->available);
    }

    /**
     * Case when options' permutations require both items' quantity to be greater than 0, restocking only 1 item.
     */
    public function testRestockUnavailable()
    {
        $schemaOne = Schema::factory()->create([
            'type' => SchemaType::SELECT,
            'required' => true,
        ]);
        $schemaTwo = Schema::factory()->create([
            'type' => SchemaType::SELECT,
            'required' => true,
        ]);

        $optionOne = Option::factory()->create([
            'schema_id' => $schemaOne->getKey(),
        ]);

        $optionTwo = Option::factory()->create([
            'schema_id' => $schemaOne->getKey(),
        ]);

        $optionThree = Option::factory()->create([
            'schema_id' => $schemaTwo->getKey(),
        ]);

        $optionFour = Option::factory()->create([
            'schema_id' => $schemaTwo->getKey(),
        ]);

        $itemOne = Item::factory()->create([
            'quantity' => 0,
        ]);
        $itemOne->options()->saveMany([$optionOne, $optionTwo]);

        $itemTwo = Item::factory()->create([
            'quantity' => 0,
        ]);
        $itemTwo->options()->saveMany([$optionThree, $optionFour]);

        $this->product->schemas()->saveMany([$schemaOne, $schemaTwo]);

        $this->actingAs($this->user)->postJson('/items/id:' . $itemTwo->getKey() . '/deposits', [
            'quantity' => 20,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'available' => false,
        ]);

    }

    /**
     * Case when permutation requires one item with greater quantity.
     */
    public function testProductRequiresHigherQuantityItem() {
        $schemaOne = Schema::factory()->create([
            'type' => SchemaType::SELECT,
            'required' => true,
        ]);
        $schemaTwo = Schema::factory()->create([
            'type' => SchemaType::SELECT,
            'required' => true,
        ]);

        $optionOne = Option::factory()->create([
            'schema_id' => $schemaOne->getKey(),
            'disabled' => 0,
        ]);

        $optionTwo = Option::factory()->create([
            'schema_id' => $schemaTwo->getKey(),
            'disabled' => 0,
        ]);

        $item = Item::factory()->create([
            'quantity' => 0,
        ]);
        $item->options()->saveMany([$optionOne, $optionTwo]);

        $this->product->schemas()->saveMany([$schemaOne, $schemaTwo]);

        $this->actingAs($this->user)->postJson('/items/id:' . $item->getKey() . '/deposits', [
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'available' => true,
        ]);
    }
}

