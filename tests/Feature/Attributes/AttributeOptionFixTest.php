<?php

namespace Tests\Feature\Attributes;

use Domain\ProductAttribute\Enums\AttributeType;
use Domain\ProductAttribute\Models\Attribute;
use Tests\TestCase;

class AttributeOptionFixTest extends TestCase
{
    public function testFixNumberOptions(): void
    {
        $attribute = Attribute::factory()->create([
            'type' => AttributeType::NUMBER->value,
        ]);

        $option1 = $attribute->options()->create([
            'name' => '250',
            'value_number' => 250,
            'index' => 0,
        ]);

        $option2 = $attribute->options()->create([
            'name' => '123',
            'value_number' => null,
            'index' => 1,
        ]);

        $option3 = $attribute->options()->create([
            'name' => '12.34',
            'value_number' => null,
            'index' => 2,
        ]);

        $option4 = $attribute->options()->create([
            'name' => 'Not valid number',
            'value_number' => null,
            'index' => 3,
        ]);

        $this->artisan('attributes:number-options')->expectsOutput('Done.');

        $this->assertDatabaseHas('attribute_options', [
            'id' => $option1->getKey(),
            'value_number' => 250,
        ]);

        $this->assertDatabaseHas('attribute_options', [
            'id' => $option2->getKey(),
            'value_number' => 123,
        ]);

        $this->assertDatabaseHas('attribute_options', [
            'id' => $option3->getKey(),
            'value_number' => 12.34,
        ]);

        $this->assertSoftDeleted('attribute_options', [
            'id' => $option4->getKey(),
        ]);
    }

    public function testFixDateOptions(): void
    {
        $attribute = Attribute::factory()->create([
            'type' => AttributeType::DATE->value,
        ]);

        $option1 = $attribute->options()->create([
            'name' => '2024-02-07',
            'value_date' => '2024-02-07',
            'index' => 0,
        ]);

        $option2 = $attribute->options()->create([
            'name' => '05-12-2023',
            'value_date' => null,
            'index' => 1,
        ]);

        $option3 = $attribute->options()->create([
            'name' => '12.10.2012',
            'value_date' => null,
            'index' => 2,
        ]);

        $option4 = $attribute->options()->create([
            'name' => 'Not valid date',
            'value_date' => null,
            'index' => 3,
        ]);

        $option5 = $attribute->options()->create([
            'name' => '2015-10-12',
            'value_date' => null,
            'index' => 4,
        ]);

        $option6 = $attribute->options()->create([
            'name' => '2015-13-12',
            'value_date' => null,
            'index' => 5,
        ]);

        $option7 = $attribute->options()->create([
            'name' => '32.13.2999',
            'value_date' => null,
            'index' => 6,
        ]);

        $this->artisan('attributes:date-options')->expectsOutput('Done.');

        $this->assertDatabaseHas('attribute_options', [
            'id' => $option1->getKey(),
            'value_date' => '2024-02-07',
            'deleted_at' => null,
        ]);

        $this->assertDatabaseHas('attribute_options', [
            'id' => $option2->getKey(),
            'value_date' => '2023-12-05',
            'deleted_at' => null,
        ]);

        $this->assertDatabaseHas('attribute_options', [
            'id' => $option3->getKey(),
            'value_date' => '2012-10-12',
            'deleted_at' => null,
        ]);

        $this->assertDatabaseHas('attribute_options', [
            'id' => $option5->getKey(),
            'value_date' => '2015-10-12',
            'deleted_at' => null,
        ]);

        $this->assertSoftDeleted('attribute_options', [
            'id' => $option4->getKey(),
        ]);

        $this->assertSoftDeleted('attribute_options', [
            'id' => $option6->getKey(),
        ]);

        $this->assertSoftDeleted('attribute_options', [
            'id' => $option7->getKey(),
        ]);
    }
}
