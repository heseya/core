<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\ProductSet;
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

        $this->actingAs($this->$user)->getJson('/filters')
            ->assertJsonCount(3, 'data');
    }

    /**
     * @dataProvider authProvider
     */
    public function testFilterWithSetsIds($user): void
    {
        $optionOne = AttributeOption::create([
            'value_text' => 'test',
            'value' => 1,
        ]);
        $optionTwo = AttributeOption::create([
            'value_text' => 'test2',
            'value' => 99,
        ]);

        $singleOptionAttribute = Attribute::factory()->create([
            'global' => 0,
            'type' => 'single-option',
        ]);
        $singleOptionAttribute->options()->saveMany([
            $optionOne,
            $optionTwo,
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
                'global' => 0,
                'type' => 'number',
            ])->getKey(),
            Attribute::factory()->create([
                'global' => 1,
                'type' => 'date',
            ])->getKey(),
            Attribute::factory()->create([
                'global' => 0,
                'type' => 'single-option',
            ])->getKey(),
        ]);

        $response = $this->actingAs($this->$user)
            ->getJson('/filters?sets[]=' . $firstProductSet->getKey() . '&sets[]=' . $secondProductSet->getKey());

        $response
            ->assertJsonFragment([
                'value_text' => 'test',
                'value' => 1,
            ])
            ->assertJsonFragment([
                'value_text' => 'test2',
                'value' => 99,
            ])
            ->assertJsonCount(6, 'data');
    }
}
