<?php

namespace Tests\Feature;

use App\Enums\RoleType;
use App\Models\Consent;
use App\Models\Role;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ConsentTest extends TestCase
{
    private Consent $consent;
    private Consent $requiredConsent;

    public function setUp(): void
    {
        parent::setUp();
        $this->consent = Consent::factory()->create(['required' => false]);

        $this->requiredConsent = Consent::factory()->create([
            'name' => 'aTest',
            'required' => true,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexUnauthorized($user): void
    {
        Consent::factory()->count(10)->create();

        $response = $this->actingAs($this->{$user})->json('get', '/consents');

        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex($user): void
    {
        $this->{$user}->givePermissionTo('consents.show');

        Consent::factory()->count(10)->create();

        $response = $this->actingAs($this->{$user})->json('get', '/consents');

        $response->assertOk();
        $response->assertJsonCount(12, 'data');
    }

    /**
     * @dataProvider authProvider
     */
    public function testShow($user): void
    {
        $this->{$user}->givePermissionTo('consents.show_details');

        Consent::factory()->count(10)->create();
        $consent = Consent::factory()->create();

        $response = $this->actingAs($this->{$user})->json('get', '/consents/id:' . $consent->getKey());

        $response
            ->assertOk()
            ->assertJson(['data' => [
                'id' => $consent->getKey(),
                'name' => $consent->name,
                'description_html' => $consent->description_html,
                'required' => $consent->required,
            ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowUnauthorized($user): void
    {
        Consent::factory()->count(10)->create();
        $consent = Consent::factory()->create();

        $response = $this->actingAs($this->{$user})->json('get', '/consents/id:' . $consent->getKey());

        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testStoreUnauthorized($user): void
    {
        $consent = Consent::factory()->make();

        $response = $this->actingAs($this->{$user})->json('post', '/consents', $consent->toArray());

        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testStore($user): void
    {
        $this->{$user}->givePermissionTo('consents.add');

        $consent = Consent::factory()->make();

        $response = $this->actingAs($this->{$user})->json('post', '/consents', $consent->toArray());

        $response->assertCreated();
        $response->assertJsonFragment($consent->toArray());

        $this->assertDatabaseCount('consents', 3)
            ->assertDatabaseHas('consents', [
                'id' => $response->getData()->data->id,
            ] + $consent->toArray());
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateUnauthorized($user): void
    {
        $consent = Consent::factory()->make();

        $response = $this->actingAs($this->{$user})
            ->json('patch', '/consents/id:' . $this->consent->getKey(), $consent->toArray());

        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testFullUpdate($user): void
    {
        $this->{$user}->givePermissionTo('consents.edit');

        $consent = Consent::factory()->make();

        $response = $this->actingAs($this->{$user})
            ->json('patch', '/consents/id:' . $this->consent->getKey(), $consent->toArray());

        $response->assertOk();
        $response->assertJsonFragment($consent->toArray());

        $this->assertDatabaseCount('consents', 2)
            ->assertDatabaseHas('consents', $consent->toArray());
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdate($user): void
    {
        $this->{$user}->givePermissionTo('consents.edit');

        $data = ['name' => 'updated'];

        $response = $this->actingAs($this->{$user})
            ->json('patch', '/consents/id:' . $this->consent->getKey(), $data);

        $response->assertOk();
        $response->assertJsonFragment([
            'name' => 'updated',
            'description_html' => $this->consent->description_html,
            'required' => $this->consent->required,
        ]);

        $this->assertDatabaseCount('consents', 2)
            ->assertDatabaseHas('consents', [
                'name' => 'updated',
                'description_html' => $this->consent->description_html,
                'required' => $this->consent->required,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteUnauthorized($user): void
    {
        $response = $this->actingAs($this->{$user})
            ->json('delete', '/consents/id:' . $this->consent->getKey());

        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDelete($user): void
    {
        $this->{$user}->givePermissionTo('consents.remove');

        $response = $this->actingAs($this->{$user})
            ->json('delete', '/consents/id:' . $this->consent->getKey());

        $response->assertStatus(204);

        $this->assertDatabaseMissing('consents', $this->consent->toArray());
    }

    public function testRegisterWithoutConsentWhenExists(): void
    {
        $role = Role::where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $role->givePermissionTo('auth.register');

        Consent::factory()->create([
            'required' => true,
        ]);

        $response = $this->json('POST', '/register', [
            'name' => 'test',
            'email' => 'test@test.test',
            'password' => 'TestTset432!!',
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'You must accept the required consents.']);
    }

    public function testRegisterWitConsent(): void
    {
        Notification::fake();

        $role = Role::where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $role->givePermissionTo('auth.register');

        $response = $this->json('POST', '/register', [
            'name' => 'test',
            'email' => 'test@test.test',
            'password' => 'TestTset432!!',
            'consents' => [
                $this->requiredConsent->getKey() => true,
            ],
        ]);

        $response->assertCreated()
            ->assertJsonFragment([
                'name' => 'test',
                'email' => 'test@test.test',
            ]);

        $this->assertDatabaseHas('consent_user', [
            'user_id' => $response->getData()->data->id,
            'consent_id' => $this->requiredConsent->getKey(),
            'value' => true,
        ]);
    }

    public function testRelationship(): void
    {
        $consentOne = Consent::factory()->create();
        $consentTwo = Consent::factory()->create();

        $this->user->consents()->save($consentOne, ['value' => true]);
        $this->user->consents()->save($consentTwo, ['value' => false]);

        $response = $this->actingAs($this->user)->json('GET', '/auth/profile');

        $this
            ->assertDatabaseCount('consent_user', 2)
            ->assertDatabaseHas('consent_user', [
                'consent_id' => $consentOne->getKey(),
                'user_id' => $this->user->getKey(),
                'value' => true,
            ])
            ->assertDatabaseHas('consent_user', [
                'consent_id' => $consentTwo->getKey(),
                'user_id' => $this->user->getKey(),
                'value' => false,
            ]);

        $response->assertJsonCount(2, 'data.consents');
        $response->assertJsonFragment([
            'name' => $consentOne->name,
            'description_html' => $consentOne->description_html,
            'required' => $consentOne->required,
            'value' => true,
        ]);
        $response->assertJsonFragment([
            'name' => $consentTwo->name,
            'description_html' => $consentTwo->description_html,
            'required' => $consentTwo->required,
            'value' => false,
        ]);
    }

    public function testUpdateUserConsents(): void
    {
        $consent = Consent::factory()->create([
            'name' => 'bTest',
            'required' => false,
        ]);

        $this->user->consents()->save($consent, ['value' => false]);

        $response = $this->actingAs($this->user)->json('PATCH', '/auth/profile', [
            'name' => 'test test',
            'consents' => [
                $this->requiredConsent->getKey() => true,
                $consent->getKey() => true,
            ],
        ]);

        $response->assertOk();
        $response->assertJsonFragment([
            'name' => $this->requiredConsent->name,
            'description_html' => $this->requiredConsent->description_html,
            'required' => $this->requiredConsent->required,
            'value' => true,
        ]);
        $response->assertJsonFragment([
            'name' => $consent->name,
            'description_html' => $consent->description_html,
            'required' => $consent->required,
            'value' => true,
        ]);

        $this->assertDatabaseCount('consent_user', 2)
            ->assertDatabaseHas('consent_user', [
                'consent_id' => $this->requiredConsent->getKey(),
                'user_id' => $this->user->getKey(),
                'value' => true,
            ])
            ->assertDatabaseHas('consent_user', [
                'consent_id' => $consent->getKey(),
                'user_id' => $this->user->getKey(),
                'value' => true,
            ]);
    }

    public function testCanUpdateProfileWithoutConsents(): void
    {
        $response = $this->actingAs($this->user)->json('PATCH', '/auth/profile', [
            'name' => 'test test',
        ]);
        $response->assertOk()
            ->assertJson(['data' => [
                'consents' => [],
            ],
            ])
            ->assertJsonFragment(['name' => 'test test']);

        $this->assertDatabaseMissing('consent_user', [
            'user_id' => $this->user->getKey(),
            'consent_id' => $this->requiredConsent->getKey(),
        ]);
    }
}
