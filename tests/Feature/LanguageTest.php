<?php

namespace Tests\Feature;

use App\Events\LanguageCreated;
use App\Events\LanguageDeleted;
use App\Events\LanguageUpdated;
use App\Listeners\WebHookEventListener;
use App\Models\Language;
use App\Models\Product;
use App\Models\WebHook;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Bus;
use Spatie\WebhookServer\CallWebhookJob;
use Tests\TestCase;

class LanguageTest extends TestCase
{
    public Language $language;
    public Language $languageHidden;

    public function setUp(): void
    {
        parent::setUp();

        Language::query()->delete();

        $this->language = Language::create([
            'iso' => 'pl',
            'name' => 'Polski',
            'default' => true,
            'hidden' => false,
        ]);

        App::setLocale($this->language->getKey());

        $this->languageHidden = Language::create([
            'iso' => 'en',
            'name' => 'English',
            'default' => false,
            'hidden' => true,
        ]);
    }

    public function testIndex(): void
    {
        $this
            ->json('GET', '/languages')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $this->language->getKey()])
            ->assertJsonMissing(['id' => $this->languageHidden->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexHidden($user): void
    {
        $this->$user->givePermissionTo('languages.show_hidden');

        $this
            ->actingAs($this->$user)
            ->json('GET', '/languages')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $this->language->getKey()])
            ->assertJsonFragment(['id' => $this->languageHidden->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithoutPermissions($user): void
    {
        $this
            ->actingAs($this->$user)
            ->json('POST', '/languages', [
                'iso' => 'es',
                'name' => 'Spain',
                'hidden' => false,
                'default' => true,
            ])
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreate($user): void
    {
        $this->$user->givePermissionTo('languages.add');

        $this
            ->actingAs($this->$user)
            ->json('POST', '/languages', [
                'iso' => 'es',
                'name' => 'Spain',
                'hidden' => false,
                'default' => true,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('languages', [
            'iso' => 'es',
            'name' => 'Spain',
            'hidden' => false,
            'default' => true,
        ]);

        // check if default language changed
        $this->assertDatabaseHas('languages', [
            'iso' => 'pl',
            'default' => false,
        ]);

        $this->assertEquals('es', Language::default()->iso);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithWebHookDispatched($user): void
    {
        $this->$user->givePermissionTo('languages.add');

        $webHook = WebHook::factory()->create([
            'events' => [
                'LanguageCreated'
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => true,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $response = $this
            ->actingAs($this->$user)
            ->json('POST', '/languages', [
                'iso' => 'nl',
                'name' => 'Netherland',
                'hidden' => false,
                'default' => false,
            ]);

        $response->assertCreated();

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof LanguageCreated;
        });

        $language = Language::find($response->getData()->data->id);

        $event = new LanguageCreated($language);
        $listener = new WebHookEventListener();
        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $language) {
            $payload = $job->payload;
            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $language->getKey()
                && $payload['data_type'] === 'Language'
                && $payload['event'] === 'LanguageCreated';
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithoutPermissions($user): void
    {
        $this
            ->actingAs($this->$user)
            ->json('PATCH', "/languages/id:{$this->language->getKey()}", [
                'iso' => 'es',
                'name' => 'Spain',
                'hidden' => false,
                'default' => true,
            ])
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdate($user): void
    {
        $this->$user->givePermissionTo('languages.edit');

        $this
            ->actingAs($this->$user)
            ->json('PATCH', "/languages/id:{$this->languageHidden->getKey()}", [
                'iso' => 'es',
                'name' => 'Spain',
                'hidden' => false,
                'default' => true,
            ])
            ->assertOk();

        $this->assertDatabaseHas('languages', [
            'iso' => 'es',
            'name' => 'Spain',
            'hidden' => false,
            'default' => true,
        ]);

        // check if default language changed
        $this->assertDatabaseHas('languages', [
            'iso' => 'pl',
            'default' => false,
        ]);

        $this->assertEquals('es', Language::default()->iso);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithWebHookDispatched($user): void
    {
        $this->$user->givePermissionTo('languages.edit');

        $webHook = WebHook::factory()->create([
            'events' => [
                'LanguageUpdated'
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $this
            ->actingAs($this->$user)
            ->json('PATCH', "/languages/id:{$this->language->getKey()}", [
                'iso' => 'nl',
                'name' => 'Netherland',
                'hidden' => false,
                'default' => true,
            ])
            ->assertOk();

        $this->assertDatabaseHas('languages', [
            'iso' => 'nl',
            'name' => 'Netherland',
            'hidden' => false,
            'default' => true,
        ]);

        // check if default language changed
        $this->assertDatabaseHas('languages', [
            'iso' => 'en',
            'default' => false,
        ]);

        $this->assertEquals('nl', Language::default()->iso);

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof LanguageUpdated;
        });

        $language = Language::find($this->language->getKey());
        $event = new LanguageUpdated($language);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $language) {
            $payload = $job->payload;
            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $language->getKey()
                && $payload['data_type'] === 'Language'
                && $payload['event'] === 'LanguageUpdated';
        });
    }

    public function testDeleteUnauthorized(): void
    {
        $this
            ->json('DELETE', "/languages/id:{$this->language->getKey()}")
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDelete($user): void
    {
        $this->$user->givePermissionTo('languages.remove');

        $this
            ->actingAs($this->$user)
            ->json('DELETE', "/languages/id:{$this->languageHidden->getKey()}")
            ->assertNoContent();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithWebHookDispatched($user): void
    {
        $this->$user->givePermissionTo('languages.remove');

        $language = Language::create([
            'iso' => 'nl',
            'name' => 'Netherland',
            'hidden' => false,
            'default' => false,
            ]);

        $webHook = WebHook::factory()->create([
            'events' => [
                'LanguageDeleted'
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $this
            ->actingAs($this->$user)
            ->json('DELETE', "/languages/id:{$language->getKey()}")
            ->assertNoContent();

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof LanguageDeleted;
        });

        $language = Language::find($language->getKey());
        $event = new LanguageDeleted($language);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $language) {
            $payload = $job->payload;
            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $language->getKey()
                && $payload['data_type'] === 'Language'
                && $payload['event'] === 'LanguageUpdated';
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteDefaultLanguage($user): void
    {
        $this->$user->givePermissionTo('languages.remove');

        $this
            ->actingAs($this->$user)
            ->json('DELETE', "/languages/id:{$this->language->getKey()}")
            ->assertStatus(400);
    }

    /**
     * @dataProvider authProvider
     */
    public function testGetDefaultTranslation($user): void
    {
        $this->$user->givePermissionTo('products.show_details');

        $product = Product::factory()->create([
            'name' => 'Nazwa',
            'description_html' => 'Opis HTML',
            'description_short' => 'Krótki opis',
            'public' => true,
        ]);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products/id:' . $product->getKey())
            ->assertOk()
            ->assertJsonFragment([
                'id' => $product->getKey(),
                'name' => 'Nazwa',
                'description_html' => 'Opis HTML',
                'description_short' => 'Krótki opis',
                'iso' => $this->language->iso,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testGetRequestedTranslation($user): void
    {
        $this->$user->givePermissionTo('products.show_details');

        $newLanguage = Language::create([
            'iso' => 'de',
            'name' => 'Deutsch',
            'default' => false,
            'hidden' => false,
        ]);

        App::setLocale($newLanguage->getKey());
        /** @var Product $product */
        $product = Product::factory()->create([
            'name' => 'Name',
            'description_html' => 'HTML Beschreibung',
            'description_short' => 'Kurze Beschreibung',
            'public' => true,
        ]);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products/id:' . $product->getKey(), [], [
                'Accept-Language' => $newLanguage->iso,
            ])
            ->assertOk()
            ->assertJsonFragment([
                'id' => $product->getKey(),
                'name' => 'Name',
                'description_html' => 'HTML Beschreibung',
                'description_short' => 'Kurze Beschreibung',
                'iso' => $newLanguage->iso,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testGetHiddenTranslation($user): void
    {
        $this->$user->givePermissionTo('products.show_details');

        /** @var Product $product */
        $product = Product::factory()->create([
            'name' => 'Nazwa',
            'description_html' => 'Opis HTML',
            'description_short' => 'Krótki opis',
            'public' => true,
        ]);

        $product->setLocale($this->languageHidden->getKey())->update([
            'name' => 'Hidden name',
            'description_html' => 'Hidden HTML description',
            'description_short' => 'Hidden short description',
            'public' => true,
        ]);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/products/id:' . $product->getKey(), [
                'Accept-Language' => $this->languageHidden->iso,
            ])
            ->assertOk()
            ->assertJsonFragment([
                'id' => $product->getKey(),
                'name' => 'Nazwa',
                'description_html' => 'Opis HTML',
                'description_short' => 'Krótki opis',
                'iso' => $this->language->iso,
            ]);
    }
}
