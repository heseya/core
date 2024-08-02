<?php

namespace Tests\Feature\Languages;

use App\Models\Product;
use App\Models\WebHook;
use Domain\Language\Events\LanguageCreated;
use Domain\Language\Events\LanguageDeleted;
use Domain\Language\Events\LanguageUpdated;
use Domain\Language\Language;
use Domain\Organization\Models\Organization;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;
use Domain\SalesChannel\Models\SalesChannel;
use Domain\Seo\Models\SeoMetadata;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
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
    public function testIndexHidden(string $user): void
    {
        $this->{$user}->givePermissionTo('languages.show_hidden');

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/languages')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $this->language->getKey()])
            ->assertJsonFragment(['id' => $this->languageHidden->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithoutPermissions(string $user): void
    {
        $this
            ->actingAs($this->{$user})
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
    public function testCreate(string $user): void
    {
        $this->{$user}->givePermissionTo('languages.add');

        $this
            ->actingAs($this->{$user})
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
    public function testCreateHidden($user): void
    {
        $this->$user->givePermissionTo('languages.add', 'languages.show_hidden');

        $response = $this
            ->actingAs($this->$user)
            ->json('POST', '/languages', [
                'iso' => 'es',
                'name' => 'Spain',
                'hidden' => true,
                'default' => false,
            ])
            ->assertCreated();

        $hidden = $response->getData()->data->id;

        $hiddenLanguages = Cache::get('languages.hidden');
        $this->assertTrue($hiddenLanguages->contains($hidden));
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithWebHookDispatched(string $user): void
    {
        $this->{$user}->givePermissionTo('languages.add');

        WebHook::factory()->create([
            'events' => [
                'LanguageCreated',
            ],
            'model_type' => $this->{$user}::class,
            'creator_id' => $this->{$user}->getKey(),
            'with_issuer' => true,
            'with_hidden' => false,
        ]);

        Event::fake(LanguageCreated::class);

        $this
            ->actingAs($this->{$user})
            ->json('POST', '/languages', [
                'iso' => 'nl',
                'name' => 'Netherland',
                'hidden' => false,
                'default' => false,
            ])
            ->assertCreated();

        Event::assertDispatched(LanguageCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithoutPermissions(string $user): void
    {
        $this
            ->actingAs($this->{$user})
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
    public function testUpdate(string $user): void
    {
        $this->{$user}->givePermissionTo('languages.edit');

        $this
            ->actingAs($this->{$user})
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
    public function testUpdateSameIso(string $user): void
    {
        $this->{$user}->givePermissionTo('languages.edit');

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', "/languages/id:{$this->languageHidden->getKey()}", [
                'iso' => 'en',
                'name' => 'English',
                'hidden' => false,
                'default' => false,
            ])
            ->assertOk();

        $this->assertDatabaseHas('languages', [
            'iso' => 'en',
            'name' => 'English',
            'hidden' => false,
            'default' => false,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithWebHookDispatched(string $user): void
    {
        $this->{$user}->givePermissionTo('languages.edit');

        WebHook::factory()->create([
            'events' => [
                'LanguageUpdated',
            ],
            'model_type' => $this->{$user}::class,
            'creator_id' => $this->{$user}->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Event::fake(LanguageUpdated::class);

        $this
            ->actingAs($this->{$user})
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

        Event::assertDispatched(LanguageUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateToHidden($user): void
    {
        $this->$user->givePermissionTo('languages.edit');

        $language = Language::create([
            'iso' => 'nl',
            'name' => 'Netherland',
            'hidden' => false,
            'default' => false,
        ]);

        $this
            ->actingAs($this->$user)
            ->json('PATCH', "/languages/id:{$language->getKey()}", [
                'hidden' => true,
            ])
            ->assertOk();

        $hiddenLanguages = Cache::get('languages.hidden');

        $this->assertTrue($hiddenLanguages->contains($language->getKey()));
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateToHiddenWithSalesChannel($user): void
    {
        $this->$user->givePermissionTo('languages.edit');

        $language = Language::create([
            'iso' => 'nl',
            'name' => 'Netherland',
            'hidden' => false,
            'default' => false,
        ]);

        $channel = SalesChannel::factory()->create(['language_id' => $language->getKey()]);

        $this
            ->actingAs($this->$user)
            ->json('PATCH', "/languages/id:{$language->getKey()}", [
                'hidden' => true,
            ])
            ->assertOk();

        $this->assertDatabaseHas('sales_channels', [
            'id' => $channel->getKey(),
            'language_id' => $this->language->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateToVisible($user): void
    {
        $this->$user->givePermissionTo('languages.edit');

        $language = Language::create([
            'iso' => 'nl',
            'name' => 'Netherland',
            'hidden' => false,
            'default' => false,
        ]);

        Cache::put('languages.hidden', Collection::make([$language->getKey()]));

        $this
            ->actingAs($this->$user)
            ->json('PATCH', "/languages/id:{$language->getKey()}", [
                'hidden' => true,
            ])
            ->assertOk();

        $hiddenLanguages = Cache::get('languages.hidden');

        $this->assertTrue(!$hiddenLanguages->contains($language->getKey()));
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
    public function testDelete(string $user): void
    {
        $this->{$user}->givePermissionTo('languages.remove');

        $language = Language::create([
            'iso' => 'nl',
            'name' => 'Netherland',
            'hidden' => false,
            'default' => false,
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('DELETE', "/languages/id:{$language->getKey()}")
            ->assertNoContent();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteModelPublished(string $user): void
    {
        $this->{$user}->givePermissionTo('languages.remove');

        /** @var Language $language */
        $language = Language::create([
            'iso' => 'nl',
            'name' => 'Netherland',
            'hidden' => false,
            'default' => false,
        ]);

        /** @var Product $product */
        $product = Product::factory()->create([
            'public' => true,
        ]);

        $product->setLocale($language->getKey())->fill([
            'name' => 'Netherland name',
            'description_short' => 'Netherland description',
        ]);
        $product->fill([
            'published' => [$this->language->getKey(), $language->getKey()],
        ]);

        $product->save();

        /** @var SeoMetadata $seo */
        $seo = SeoMetadata::factory()->create();

        $seo->setLocale($language->getKey())->fill([
            'title' => 'Netherland title',
            'description' => 'Netherland description',
            'keywords' => ['test'],
        ]);
        $seo->fill([
            'published' => [$this->language->getKey(), $language->getKey()],
        ]);
        $seo->save();

        $product->seo()->save($seo);

        $this
            ->actingAs($this->{$user})
            ->json('DELETE', "/languages/id:{$language->getKey()}")
            ->assertNoContent();

        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            "name->{$language->getKey()}" => null,
            "description_short->{$language->getKey()}" => null,
            'published' => json_encode([$this->language->getKey()]),
        ]);

        $this->assertDatabaseHas('seo_metadata', [
            'id' => $seo->getKey(),
            "title->{$language->getKey()}" => null,
            "description->{$language->getKey()}" => null,
            "keywords->{$language->getKey()}" => null,
            'published' => json_encode([$this->language->getKey()]),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithAttributeOption(string $user): void
    {
        $this->{$user}->givePermissionTo('languages.remove');

        $language = Language::create([
            'iso' => 'nl',
            'name' => 'Netherland',
            'hidden' => false,
            'default' => false,
        ]);

        $attribute = Attribute::factory()->create();
        /** @var AttributeOption $attributeOption */
        $attributeOption = AttributeOption::factory()->create([
            'name' => 'Name',
            'attribute_id' => $attribute->getKey(),
            'index' => 1,
        ]);

        $attributeOption->setLocale($language->getKey())->fill([
            'name' => 'Netherland name',
        ]);

        $attributeOption->save();

        $this
            ->actingAs($this->{$user})
            ->json('DELETE', "/languages/id:{$language->getKey()}")
            ->assertNoContent();

        $this->assertDatabaseHas('attribute_options', [
            'id' => $attributeOption->getKey(),
            "name->{$language->getKey()}" => null,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithWebHookDispatched(string $user): void
    {
        $this->{$user}->givePermissionTo('languages.remove');

        $language = Language::create([
            'iso' => 'nl',
            'name' => 'Netherland',
            'hidden' => false,
            'default' => false,
        ]);

        WebHook::factory()->create([
            'events' => [
                'LanguageDeleted',
            ],
            'model_type' => $this->{$user}::class,
            'creator_id' => $this->{$user}->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Event::fake(LanguageDeleted::class);

        $this
            ->actingAs($this->{$user})
            ->json('DELETE', "/languages/id:{$language->getKey()}")
            ->assertNoContent();

        Event::assertDispatched(LanguageDeleted::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteDefaultLanguage(string $user): void
    {
        $this->{$user}->givePermissionTo('languages.remove');

        $this
            ->actingAs($this->{$user})
            ->json('DELETE', "/languages/id:{$this->language->getKey()}")
            ->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteHidden($user): void
    {
        $this->$user->givePermissionTo('languages.remove');

        $language = Language::create([
            'iso' => 'nl',
            'name' => 'Netherland',
            'hidden' => false,
            'default' => false,
        ]);

        Cache::put('languages.hidden', Collection::make([$language->getKey()]));

        $this
            ->actingAs($this->$user)
            ->json('DELETE', "/languages/id:{$language->getKey()}")
            ->assertNoContent();

        $hiddenLanguages = Cache::get('languages.hidden');

        $this->assertTrue(!$hiddenLanguages->contains($language->getKey()));
    }

    /**
     * @dataProvider authProvider
     */
    public function testGetDefaultTranslation(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show_details');

        $product = Product::factory()->create([
            'name' => 'Nazwa',
            'description_html' => 'Opis HTML',
            'description_short' => 'Krótki opis',
            'public' => true,
        ]);

        $this
            ->actingAs($this->{$user})
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
    public function testGetRequestedTranslation(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show_details');

        /** @var Language $newLanguage */
        $newLanguage = Language::query()->create([
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
            ->actingAs($this->{$user})
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
    public function testGetHiddenTranslation(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show_details');

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
            ->actingAs($this->{$user})
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

    /**
     * @dataProvider authProvider
     */
    public function testUncheckCurrentDefaultLanguage(string $user): void
    {
        $this->{$user}->givePermissionTo('languages.edit');

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', "/languages/id:{$this->language->getKey()}", [
                'iso' => 'es',
                'name' => 'Espanol',
                'hidden' => false,
                'default' => false,
            ])
            ->assertUnprocessable()
            ->assertJsonFragment(['message' => 'There must be exactly one default language.']);

        $this->assertDatabaseHas('languages', [
            'id' => $this->language->getKey(),
            'default' => true,
        ]);
    }
}
