<?php

use Domain\Redirect\Enums\RedirectType;
use Domain\Redirect\Events\RedirectCreated;
use Domain\Redirect\Events\RedirectDeleted;
use Domain\Redirect\Events\RedirectUpdated;
use Domain\Redirect\Models\Redirect;
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

        $this->assertQueryCountLessThan(8);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexFilteredByEnabled($user): void
    {
        Redirect::factory()->count(5)->create([
            'enabled' => true,
        ]);

        $redirect = Redirect::factory()->create([
            'enabled' => false,
        ]);

        $this->{$user}->givePermissionTo('redirects.show');

        $response = $this->actingAs($this->{$user})
            ->getJson('/redirects?enabled=0')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $redirect->getKey(),
            ]);

        $this->assertQueryCountLessThan(8);
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
    public function testShow($user): void
    {
        $redirect = Redirect::factory()->create();
        Redirect::factory()->count(5)->create();

        $this->{$user}->givePermissionTo('redirects.show');

        $this->actingAs($this->{$user})
            ->getJson("/redirects/id:{$redirect->getKey()}")
            ->assertJsonFragment([
                'id' => $redirect->getKey(),
                'name' => $redirect->name,
                'source_url' => $redirect->source_url,
                'target_url' => $redirect->target_url,
                'type' => $redirect->type->value,
                'enabled' => $redirect->enabled,
            ]);
    }

    public function testShowUnauthorized(): void
    {
        $redirect = Redirect::factory()->create();
        Redirect::factory()->count(5)->create();

        $this->getJson("/redirects/id:{$redirect->getKey()}")
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
                'source_url' => 'source_url',
                'target_url' => 'http://example.com',
                'type' => RedirectType::TEMPORARY_REDIRECT->value,
            ])
            ->assertCreated()
            ->assertJsonFragment([
                'name' => 'test',
                'source_url' => 'source_url',
                'target_url' => 'http://example.com',
                'type' => RedirectType::TEMPORARY_REDIRECT->value,
            ]);

        $this->assertDatabaseHas('redirects', [
            'name' => 'test',
            'source_url' => 'source_url',
            'target_url' => 'http://example.com',
            'type' => RedirectType::TEMPORARY_REDIRECT->value,
        ]);

        Event::assertDispatched(RedirectCreated::class);
    }

    public function testCreateUnauthorized(): void
    {
        $this->postJson('/redirects', [
            'name' => 'test',
            'source_url' => 'test_source_url',
            'target_url' => 'http://example.com',
            'type' => RedirectType::TEMPORARY_REDIRECT->value,
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
                'source_url' => 'test_source_url',
                'target_url' => 'http://example.com',
                'type' => RedirectType::TEMPORARY_REDIRECT->value,
            ])
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'test',
                'source_url' => 'test_source_url',
                'target_url' => 'http://example.com',
                'type' => RedirectType::TEMPORARY_REDIRECT->value,
            ]);

        $this->assertDatabaseHas('redirects', [
            'name' => 'test',
            'source_url' => 'test_source_url',
            'target_url' => 'http://example.com',
            'type' => RedirectType::TEMPORARY_REDIRECT->value,
        ]);

        Event::assertDispatched(RedirectUpdated::class);
    }

    public function testUpdateUnauthorized(): void
    {
        $redirect = Redirect::factory()->create();

        $this->patchJson("/redirects/id:{$redirect->getKey()}", [
            'name' => 'test',
            'source_url' => 'test_source_url',
            'target_url' => 'http://example.com',
            'type' => RedirectType::TEMPORARY_REDIRECT->value,
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
