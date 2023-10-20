<?php

namespace Tests\Feature;

use App\Models\App as Application;
use App\Models\User;
use Domain\App\Models\AppWidget;
use Illuminate\Support\Arr;
use Tests\TestCase;

class AppWidgetTest extends TestCase
{
    private Application $application2;

    public function setUp(): void
    {
        parent::setUp();

        $this->application2 = Application::factory()->create();

        AppWidget::factory()->count(3)->create([
            'app_id' => $this->application->getKey(),
        ]);
        AppWidget::factory()->count(3)->create([
            'app_id' => $this->application2->getKey(),
        ]);
    }

    public function testListWidgetsForbidden(): void
    {
        $response = $this->actingAs($this->user)->json('GET', '/app-widgets');

        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testListWidgets($user): void
    {
        $this->{$user}->givePermissionTo('app_widgets.show');

        $response = $this->actingAs($this->{$user})->json('GET', '/app-widgets');

        $response->assertOk();

        if ($this->{$user} instanceof User) {
            $response->assertJsonCount(6, 'data');

            $data = $response->json('data');
            foreach ($data as $widget) {
                $this->assertContains($widget['app']['id'], [$this->application->getKey(), $this->application2->getKey()]);
            }
        } else {
            $response->assertJsonCount(3, 'data');

            $data = $response->json('data');
            foreach ($data as $widget) {
                $this->assertNotEquals($this->application2->getKey(), $widget['app']['id']);
            }
        }
    }

    public function testListWidgetsWithPermissions(): void
    {
        $this->user->givePermissionTo('app_widgets.show');
        $this->user->givePermissionTo('products.show');

        /** @var AppWidget $widget */
        $widget1 = AppWidget::where('app_id', $this->application->getKey())->first();
        $widget1->syncPermissions('products.show');
        $widget2 = AppWidget::where('app_id', $this->application2->getKey())->first();
        $widget2->syncPermissions('products.show', 'products.add');

        $response = $this->actingAs($this->user)->json('GET', '/app-widgets');
        $response->assertOk();
        $response->assertJsonCount(5, 'data');

        $data = $response->json('data');

        $ids = [];
        foreach ($data as $widget) {
            $ids[] = $widget['id'];
            $this->assertContains($widget['app']['id'], [$this->application->getKey(), $this->application2->getKey()]);
            $this->assertNotEquals($widget2->getKey(), $widget['id']);
        }

        $this->assertContains($widget1->getKey(), $ids);
        $this->assertNotContains($widget2->getKey(), $ids);
    }

    /**
     * @dataProvider authProvider
     */
    public function testListWidgetsForSection($user): void
    {
        $this->{$user}->givePermissionTo('app_widgets.show');

        $widget = AppWidget::query()->where('app_id', $this->application->getKey())->first();

        $response = $this->actingAs($this->{$user})->json('GET', '/app-widgets?' . Arr::query(['section' => $widget->section]));
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJson([
            'data' => [
                [
                    'id' => $widget->getKey(),
                ]
            ]
        ]);
    }

    public function testWidgetsAddForbidden(): void
    {
        $response = $this->actingAs($this->user)->json('POST', '/app-widgets', AppWidget::factory()->make()->toArray());
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testWidgetsAdd($user): void
    {
        $this->{$user}->givePermissionTo('app_widgets.add');

        $response = $this->actingAs($this->{$user})->json('POST', '/app-widgets', AppWidget::factory()->make()->toArray());
        $response->assertValid()->assertCreated();
        $response->assertJsonCount(6, 'data');
    }

    public function testWidgetsEditWrongApplication(): void
    {
        $widget = AppWidget::query()->where('app_id', $this->application2->getKey())->first();
        $response = $this->actingAs($this->application)->json('PATCH', '/app-widgets/id:' . $widget->getKey(), AppWidget::factory()->make()->toArray());
        $response->assertForbidden();
    }

    public function testWidgetsEditForbidden(): void
    {
        $widget = AppWidget::query()->where('app_id', $this->application->getKey())->first();
        $response = $this->actingAs($this->user)->json('PATCH', '/app-widgets/id:' . $widget->getKey(), AppWidget::factory()->make()->toArray());
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testWidgetsEdit($user): void
    {
        $this->{$user}->givePermissionTo('app_widgets.edit');

        $widget = AppWidget::query()->where('app_id', $this->application->getKey())->first();

        $response = $this->actingAs($this->{$user})->json('PATCH', '/app-widgets/id:' . $widget->getKey(), AppWidget::factory()->make()->toArray());
        $response->assertValid()->assertOk();
        $response->assertJsonCount(6, 'data');
    }

    /**
     * @dataProvider authProvider
     */
    public function testWidgetsDelete($user): void
    {
        $this->{$user}->givePermissionTo('app_widgets.remove');

        $widget = AppWidget::query()->where('app_id', $this->application->getKey())->first();

        $response = $this->actingAs($this->{$user})->json('DELETE', '/app-widgets/id:' . $widget->getKey());
        $response->assertNoContent();
    }
}
