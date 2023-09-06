<?php

use App\Enums\RedirectType;
use App\Events\RedirectCreated;
use App\Events\RedirectDeleted;
use App\Events\RedirectUpdated;
use App\Models\Redirect;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RedirectTest extends TestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testIndex($user): void
    {
        Redirect::factory()->count(5)->create();

        $this->{$user}->givePermissionTo('redirects.show');

        $this->actingAs($this->{$user})
            ->getJson('/redirects')
            ->assertOk()
            ->assertJsonCount(5, 'data');

        $this->assertQueryCountLessThan(5);
    }

    public function testIndexUnauthorized(): void
    {
        Redirect::factory()->count(5)->create();

        $this->getJson('/redirects')
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreate($user): void
    {
        Event::fake([RedirectCreated::class]);

        $this->{$user}->givePermissionTo('redirects.add');

        $this->actingAs($this->{$user})
            ->postJson('/redirects', [
                'name' => 'test',
                'slug' => 'test_slug',
                'url' => 'http://example.com',
                'type' => RedirectType::TEMPORARY->value,
            ])
            ->assertCreated()
            ->assertJsonFragment([
                'name' => 'test',
                'slug' => 'test_slug',
                'url' => 'http://example.com',
                'type' => RedirectType::TEMPORARY->value,
            ]);

        $this->assertDatabaseHas('redirects', [
            'name' => 'test',
            'slug' => 'test_slug',
            'url' => 'http://example.com',
            'type' => RedirectType::TEMPORARY->value,
        ]);

        Event::assertDispatched(RedirectCreated::class);
    }

    public function testCreateUnauthorized(): void
    {
        $this->postJson('/redirects', [
            'name' => 'test',
            'slug' => 'test_slug',
            'url' => 'http://example.com',
            'type' => RedirectType::TEMPORARY->value,
        ])
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdate($user): void
    {
        Event::fake([RedirectUpdated::class]);

        $redirect = Redirect::factory()->create();

        $this->{$user}->givePermissionTo('redirects.edit');

        $this->actingAs($this->{$user})
            ->patchJson("/redirects/id:{$redirect->getKey()}", [
                'name' => 'test',
                'slug' => 'test_slug',
                'url' => 'http://example.com',
                'type' => RedirectType::TEMPORARY->value,
            ])
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'test',
                'slug' => 'test_slug',
                'url' => 'http://example.com',
                'type' => RedirectType::TEMPORARY->value,
            ]);

        $this->assertDatabaseHas('redirects', [
            'name' => 'test',
            'slug' => 'test_slug',
            'url' => 'http://example.com',
            'type' => RedirectType::TEMPORARY->value,
        ]);

        Event::assertDispatched(RedirectUpdated::class);
    }

    public function testUpdateUnauthorized(): void
    {
        $redirect = Redirect::factory()->create();

        $this->patchJson("/redirects/id:{$redirect->getKey()}", [
            'name' => 'test',
            'slug' => 'test_slug',
            'url' => 'http://example.com',
            'type' => RedirectType::TEMPORARY->value,
        ])
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDelete($user): void
    {
        Event::fake([RedirectDeleted::class]);

        $redirect = Redirect::factory()->create();

        $this->{$user}->givePermissionTo('redirects.remove');

        $this->actingAs($this->{$user})
            ->deleteJson("/redirects/id:{$redirect->getKey()}")
            ->assertNoContent();

        $this->assertDatabaseMissing('redirects', [
            'id' => $redirect->getKey(),
        ]);

        Event::assertDispatched(RedirectDeleted::class);
    }

    public function testDeleteUnauthorized(): void
    {
        $redirect = Redirect::factory()->create();

        $this->deleteJson("/redirects/id:{$redirect->getKey()}")
            ->assertForbidden();
    }
}
