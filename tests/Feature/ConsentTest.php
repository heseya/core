<?php

namespace Tests\Feature;

use App\Enums\RoleType;
use App\Models\Role;
use Domain\Consent\Models\Consent;
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
    public function testIndexUnauthorized(string $user): void
    {
        Consent::factory()->count(10)->create();

        $response = $this->actingAs($this->{$user})->json('get', '/consents');

        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex(string $user): void
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
    public function testShow(string $user): void
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
    public function testShowUnauthorized(string $user): void
    {
        Consent::factory()->count(10)->create();
        $consent = Consent::factory()->create();

        $response = $this->actingAs($this->{$user})->json('get', '/consents/id:' . $consent->getKey());

        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testStoreUnauthorized(string $user): void
    {
        $consent = Consent::factory()->make();

        $response = $this->actingAs($this->{$user})->json('post', '/consents', $consent->toArray());

        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testStore(string $user): void
    {
        $this->{$user}->givePermissionTo('consents.add');

        $response = $this->actingAs($this->{$user})->json('post', '/consents', [
            'translations' => [
                $this->lang => [
                    'name' => 'New consent',
                    'description_html' => '<p>Lorem ipsum</p>',
                ],
            ],
            'required' => false,
        ]);

        $response->assertCreated();
        $response->assertJsonFragment([
            'name' => 'New consent',
            'description_html' => '<p>Lorem ipsum</p>',
            'required' => false,
        ]);

        $this
            ->assertDatabaseCount('consents', 3)
            ->assertDatabaseHas('consents', [
                'id' => $response->getData()->data->id,
                "name->{$this->lang}" => 'New consent',
                "description_html->{$this->lang}" => '<p>Lorem ipsum</p>',
                'required' => false,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateUnauthorized(string $user): void
    {
        $consent = Consent::factory()->make();

        $response = $this->actingAs($this->{$user})
            ->json('patch', '/consents/id:' . $this->consent->getKey(), $consent->toArray());

        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdate(string $user): void
    {
        $this->{$user}->givePermissionTo('consents.edit');

        $response = $this->actingAs($this->{$user})
            ->json('patch', '/consents/id:' . $this->consent->getKey(), [
                'translations' => [
                    $this->lang => [
                        'name' => 'Updated name',
                        'description_html' => '<p>Lorem ipsum</p>',
                    ],
                ],
                'required' => false,
            ]);

        $response->assertOk();
        $response->assertJsonFragment([

        ]);

        $this->assertDatabaseCount('consents', 2)
            ->assertDatabaseHas('consents', [
                'id' => $this->consent->getKey(),
                "name->{$this->lang}" => 'Updated name',
                "description_html->{$this->lang}" => '<p>Lorem ipsum</p>',
                'required' => false,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteUnauthorized(string $user): void
    {
        $response = $this->actingAs($this->{$user})
            ->json('delete', '/consents/id:' . $this->consent->getKey());

        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDelete(string $user): void
    {
        $this->{$user}->givePermissionTo('consents.remove');

        $response = $this->actingAs($this->{$user})
            ->json('delete', '/consents/id:' . $this->consent->getKey());

        $response->assertStatus(204);

        $this->assertDatabaseMissing('consents', $this->consent->toArray());
    }

    public function testRegisterWithoutConsentWhenExists(): void
    {
        /** @var Role $role */
        $role = Role::query()->where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $role->givePermissionTo('auth.register');

        Consent::factory()->create([
            'required' => true,
        ]);

        $this
            ->json('POST', '/register', [
                'name' => 'test',
                'email' => 'test@test.test',
                'password' => 'TestTset432!!',
                'consents' => [],
            ])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'You must accept the required consents.']);
    }

    public function testRegisterWitConsent(): void
    {
        Notification::fake();

        /** @var Role $role */
        $role = Role::query()->where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $role->givePermissionTo('auth.register');

        $response = $this->json('POST', '/register', [
            'name' => 'test',
            'email' => 'test@test.test',
            'password' => 'TestTset432!!',
            'consents' => [
                $this->requiredConsent->getKey() => true,
            ],
        ]);

        $response
            ->assertCreated()
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
