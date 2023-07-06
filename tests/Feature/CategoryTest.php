<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testGoogleCategory($user): void
    {
        Http::fake([
            '*' => Http::response(
                '# Google_Product_Taxonomy_Version: 2021-09-21
1 - Animals & Pet Supplies
3237 - Animals & Pet Supplies > Live Animals
2 - Animals & Pet Supplies > Pet Supplies
3 - Animals & Pet Supplies > Pet Supplies > Bird Supplies
7385 - Animals & Pet Supplies > Pet Supplies > Bird Supplies > Bird Cage Accessories
499954 - Animals & Pet Supplies > Pet Supplies > Bird Supplies > Bird Cage Accessories > Bird Cage Bird Baths
7386 - Animals & Pet Supplies > Pet Supplies > Bird Supplies > Bird Cage Accessories > Bird Cage Food & Water Dishes
4989 - Animals & Pet Supplies > Pet Supplies > Bird Supplies > Bird Cages & Stands
4990 - Animals & Pet Supplies > Pet Supplies > Bird Supplies > Bird Food
7398 - Animals & Pet Supplies > Pet Supplies > Bird Supplies > Bird Gyms & Playstands
4991 - Animals & Pet Supplies > Pet Supplies > Bird Supplies > Bird Ladders & Perches
4992 - Animals & Pet Supplies > Pet Supplies > Bird Supplies > Bird Toys
4993 - Animals & Pet Supplies > Pet Supplies > Bird Supplies > Bird Treats
4 - Animals & Pet Supplies > Pet Supplies > Cat Supplies
'
            ),
        ]);
        $this->actingAs($this->{$user})->json('get', '/google-categories/en-US')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'name',
                    ],
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testGoogleCategoryFailed($user): void
    {
        $this
            ->actingAs($this->{$user})
            ->json('GET', '/google-categories/test-TEST')
            ->assertStatus(422);
    }
}
