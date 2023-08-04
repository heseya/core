<?php

namespace Tests\Feature;

use App\Models\ProductSet;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;
use Tests\TestCase;

class FilterTest extends TestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testFilterGlobal($user): void
    {
        Attribute::factory()->count(3)->create(['global' => 1]);
        Attribute::factory()->create(['global' => 0]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/filters')
            ->assertJsonCount(3, 'data');
    }

    /**
     * @dataProvider authProvider
     */
    public function testFilterWithSetsIds($user): void
    {
        $singleOptionAttribute = Attribute::factory()->create([
            'global' => 0,
            'type' => 'single-option',
        ]);

        AttributeOption::create([
            'name' => 'test',
            'value_number' => 1,
            'index' => 0,
            'attribute_id' => $singleOptionAttribute->getKey(),
        ]);
        AttributeOption::create([
            'name' => 'test2',
            'value_number' => 99,
            'index' => 1,
            'attribute_id' => $singleOptionAttribute->getKey(),
        ]);

        $firstProductSet = ProductSet::factory()->create();
        $firstProductSet->attributes()->attach([
            Attribute::factory()->create([
                'global' => 0,
                'type' => 'number',
            ])->getKey(),
            Attribute::factory()->create([
                'global' => 0,
                'type' => 'date',
            ])->getKey(),
        ]);

        $secondProductSet = ProductSet::factory()->create();
        $secondProductSet->attributes()->attach([
            Attribute::factory()->create([
                'global' => 0,
                'type' => 'number',
            ])->getKey(),
            $singleOptionAttribute->getKey(),
            Attribute::factory()->create([
                'global' => 0,
                'type' => 'date',
            ])->getKey(),
        ]);

        ProductSet::factory()->create()->attributes()->attach([
            Attribute::factory()->create([
                'name' => 'Not in query number',
                'global' => 0,
                'type' => 'number',
            ])->getKey(),
            Attribute::factory()->create([
                'global' => 1,
                'type' => 'date',
            ])->getKey(),
            Attribute::factory()->create([
                'name' => 'Not in query single option',
                'global' => 0,
                'type' => 'single-option',
            ])->getKey(),
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/filters', [
                'sets' => [$firstProductSet->getKey(), $secondProductSet->getKey()],
            ])
            ->assertJsonCount(6, 'data')
            ->assertJsonMissing([
                'name' => 'Not in query number',
            ])
            ->assertJsonMissing([
                'name' => 'Not in query single option',
            ]);
    }
}
