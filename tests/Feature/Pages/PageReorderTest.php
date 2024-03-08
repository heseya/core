<?php

namespace Tests\Feature\Pages;

use Domain\Page\Page;
use Illuminate\Support\Facades\DB;

class PageReorderTest extends PageTestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testReorderUnauthorized(string $user): void
    {
        DB::table('pages')->delete();
        $page = Page::factory()->count(10)->create();

        $this->actingAs($this->{$user})->postJson('/pages/reorder', [
            'pages' => $page->pluck('id')->toArray(),
        ])->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testReorder(string $user): void
    {
        $this->{$user}->givePermissionTo('pages.edit');

        DB::table('pages')->delete();
        $page = Page::factory()->count(3)->create();

        $ids = $page->pluck('id');

        $this->actingAs($this->{$user})->postJson('/pages/reorder', [
            'pages' => $ids->toArray(),
        ])->assertNoContent();

        $ids->each(fn ($id, $order) => $this->assertDatabaseHas('pages', [
            'id' => $id,
            'order' => $order,
        ]));
    }
}
