<?php

namespace Tests\Feature\Apps;

use App\Models\AppWidget;
use Tests\TestCase;

class AppWidgetIndexTest extends TestCase
{
    public function testIndexUnauthorized(): void
    {
        $this
            ->json('GET', '/apps/widgets')
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex($user): void
    {
        $this->$user->givePermissionTo('app_widgets.show');
        $this
            ->actingAs($this->$user)
            ->json('GET', '/apps/widgets')
            ->assertOk();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexSearch($user): void
    {
        $widget = AppWidget::query()->create([
            'section' => 'test',
        ]);
        AppWidget::query()->create([
            'section' => 'test123',
        ]);

        $this->$user->givePermissionTo('app_widgets.show');
        $this
            ->actingAs($this->$user)
            ->json('GET', '/apps/widgets', ['section' => 'test'])
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->assertDatabaseHas('app_widgets', [
            'id' => $widget->getKey(),
        ]);
    }
}
