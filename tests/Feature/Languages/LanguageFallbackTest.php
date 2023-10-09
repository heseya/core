<?php

namespace Tests\Feature\Languages;

use App\Enums\SchemaType;
use App\Models\Option;
use App\Models\Product;
use App\Models\Schema;
use Domain\Language\Enums\LangFallbackType;
use Domain\Language\Language;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class LanguageFallbackTest extends TestCase
{
    public Language $language;

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
    }

    public static function fallbackProvider(): array
    {
        return [
            'as user fallback none' => ['user', LangFallbackType::NONE, [
                'product_es' => 'Nombre',
            ]],
            'as user fallback invalid value' => ['user', 'invalid', [
                'product_es' => 'Nombre',
            ]],
            'as user fallback default' => ['user', LangFallbackType::DEFAULT, [
                'product_es' => 'Nombre',
                'product_pl' => 'Nazwa',
                'product_de' => '',
            ]],
            'as user fallback any' => ['user', LangFallbackType::ANY, [
                'product_es' => 'Nombre',
                'product_pl' => 'Nazwa',
                'product_de' => 'Name',
            ]],
            'as app fallback none' => ['application', LangFallbackType::NONE, [
                'product_es' => 'Nombre',
            ]],
            'as app fallback invalid value' => ['application', 'invalid', [
                'product_es' => 'Nombre',
            ]],
            'as app fallback default' => ['application', LangFallbackType::DEFAULT, [
                'product_es' => 'Nombre',
                'product_pl' => 'Nazwa',
                'product_de' => '',
            ]],
            'as app fallback any' => ['application', LangFallbackType::ANY, [
                'product_es' => 'Nombre',
                'product_pl' => 'Nazwa',
                'product_de' => 'Name',
            ]],
        ];
    }

    /**
     * @dataProvider fallbackProvider
     */
    public function testIndexFallback($user, $fallback, $result): void
    {
        $this->$user->givePermissionTo('products.show');

        $es = Language::create([
            'iso' => 'es',
            'name' => 'Spain',
            'default' => false,
            'hidden' => false,
        ]);

        $de = Language::create([
            'iso' => 'de',
            'name' => 'Deutsch',
            'default' => false,
            'hidden' => false,
        ]);

        App::setLocale($es->getKey());
        $product_es = Product::factory()->create([
            'name' => 'Nombre',
            'description_html' => 'HTML descripción',
            'description_short' => 'Breve descripción',
            'public' => true,
        ]);

        App::setLocale($de->getKey());
        $product_de = Product::factory()->create([
            'name' => 'Name',
            'description_html' => 'HTML Beschreibung',
            'description_short' => 'Kurze Beschreibung',
            'public' => true,
        ]);

        App::setLocale($this->language->getKey());
        $product_pl = Product::factory()->create([
            'name' => 'Nazwa',
            'description_html' => 'HTML opis',
            'description_short' => 'Krótki opis',
            'public' =>true,
        ]);

        $response = $this->actingAs($this->$user)->json('GET', 'products', [
            'lang_fallback' => $fallback,
        ], [
            'Accept-Language' => $es->iso,
        ]);

        $response->assertJsonCount(count($result), 'data');

        foreach ($result as $product => $name) {
            $response->assertJsonFragment([
                'id' => $$product->getKey(),
                'name' => $name,
            ]);
        }
    }

    public static function unpublishedFallbackNoneProvider(): array
    {
        return [
            'as user without show_hidden fallback none' => ['user', [], LangFallbackType::NONE],
            'as user without show_hidden fallback invalid' => ['user', [], 'invalid'],
            'as user with show_hidden fallback none' => ['user', ['languages.show_hidden'], LangFallbackType::NONE],
            'as user with show_hidden fallback invalid' => ['user', ['languages.show_hidden'], 'invalid'],
            'as app without show_hidden fallback none' => ['application', [], LangFallbackType::NONE],
            'as app without show_hidden fallback invalid' => ['application', [], 'invalid'],
            'as app with show_hidden fallback none' => ['application', ['languages.show_hidden'], LangFallbackType::NONE],
            'as app with show_hidden fallback invalid' => ['application', ['languages.show_hidden'], 'invalid'],
        ];
    }

    /**
     * @dataProvider unpublishedFallbackNoneProvider
     */
    public function testShowUnpublishedNone($user, $show_hidden, $fallback): void
    {
        $this->$user->givePermissionTo(array_merge(['products.show_details'], $show_hidden));

        $es = Language::create([
            'iso' => 'es',
            'name' => 'Spain',
            'default' => false,
            'hidden' => false,
        ]);

        $product = Product::factory()->create([
            'public' => true,
        ]);

        $this->actingAs($this->$user)->json('GET', 'products/id:' . $product->getKey(), [
            'lang_fallback' => $fallback,
        ], [
            'Accept-Language' => $es->iso,
        ])
            ->assertStatus(406)
            ->assertJsonStructure([
                'error' => [
                    'code',
                    'message',
                    'published',
                ],
            ])
            ->assertJsonFragment([
                'code' => 406,
                'message' => 'No content in selected language',
            ])->assertJsonFragment([
                'id' => $this->language->getKey(),
            ]);
    }

    public static function unpublishedFallbackDefaultAndAnyProvider(): array
    {
        return [
            'as user fallback default with default and another no hidden no unpublished' => [
                'user', LangFallbackType::DEFAULT, true, true, [],
                [
                    'name' => 'Nazwa',
                    'description_html' => 'HTML opis',
                    'description_short' => 'Krótki opis',
                ],
            ],
            'as user fallback default with default and another no hidden unpublished' => [
                'user', LangFallbackType::DEFAULT, true, true, ['products.show_hidden'],
                [
                    'name' => 'Nazwa',
                    'description_html' => 'HTML opis',
                    'description_short' => 'Krótki opis',
                ],
            ],
            'as user fallback default with default not published and another no hidden no unpublished' => [
                'user', LangFallbackType::DEFAULT, false, true, [],
                [
                    'name' => '',
                    'description_html' => '',
                    'description_short' => '',
                ],
            ],
            'as user fallback default with default not published and another no hidden unpublished' => [
                'user', LangFallbackType::DEFAULT, false, true, ['products.show_hidden'],
                [
                    'name' => 'Nazwa',
                    'description_html' => 'HTML opis',
                    'description_short' => 'Krótki opis',
                ],
            ],
            'as user fallback default with no default and another no hidden no unpublished' => [
                'user', LangFallbackType::DEFAULT, null, true, [],
                [
                    'name' => '',
                    'description_html' => '',
                    'description_short' => '',
                ],
            ],
            'as user fallback default with no default and another no hidden unpublished' => [
                'user', LangFallbackType::DEFAULT, null, true, ['products.show_hidden'],
                [
                    'name' => '',
                    'description_html' => '',
                    'description_short' => '',
                ],
            ],
            'as user fallback default with default and another hidden no unpublished' => [
                'user', LangFallbackType::DEFAULT, true, true, ['languages.show_hidden'],
                [
                    'name' => 'Nazwa',
                    'description_html' => 'HTML opis',
                    'description_short' => 'Krótki opis',
                ],
            ],
            'as user fallback default with default and another hidden unpublished' => [
                'user', LangFallbackType::DEFAULT, true, true, ['languages.show_hidden', 'products.show_hidden'],
                [
                    'name' => 'Nazwa',
                    'description_html' => 'HTML opis',
                    'description_short' => 'Krótki opis',
                ],
            ],
            'as user fallback default with default not published and another hidden no unpublished' => [
                'user', LangFallbackType::DEFAULT, false, true, ['languages.show_hidden'],
                [
                    'name' => '',
                    'description_html' => '',
                    'description_short' => '',
                ],
            ],
            'as user fallback default with default not published and another hidden unpublished' => [
                'user', LangFallbackType::DEFAULT, false, true, ['languages.show_hidden', 'products.show_hidden'],
                [
                    'name' => 'Nazwa',
                    'description_html' => 'HTML opis',
                    'description_short' => 'Krótki opis',
                ],
            ],
            'as user fallback default with no default and another hidden no unpublished' => [
                'user', LangFallbackType::DEFAULT, null, true, ['languages.show_hidden'],
                [
                    'name' => '',
                    'description_html' => '',
                    'description_short' => '',
                ],
            ],
            'as user fallback default with no default and another hidden unpublished' => [
                'user', LangFallbackType::DEFAULT, null, true, ['languages.show_hidden', 'products.show_hidden'],
                [
                    'name' => '',
                    'description_html' => '',
                    'description_short' => '',
                ],
            ],
            'as app fallback default with default and another no hidden no unpublished' => [
                'application', LangFallbackType::DEFAULT, true, true, [],
                [
                    'name' => 'Nazwa',
                    'description_html' => 'HTML opis',
                    'description_short' => 'Krótki opis',
                ],
            ],
            'as app fallback default with default and another no hidden unpublished' => [
                'application', LangFallbackType::DEFAULT, true, true, ['products.show_hidden'],
                [
                    'name' => 'Nazwa',
                    'description_html' => 'HTML opis',
                    'description_short' => 'Krótki opis',
                ],
            ],
            'as app fallback default with default not published and another no hidden no unpublished' => [
                'application', LangFallbackType::DEFAULT, false, true, [],
                [
                    'name' => '',
                    'description_html' => '',
                    'description_short' => '',
                ],
            ],
            'as app fallback default with default not published and another no hidden unpublished' => [
                'application', LangFallbackType::DEFAULT, false, true, ['products.show_hidden'],
                [
                    'name' => 'Nazwa',
                    'description_html' => 'HTML opis',
                    'description_short' => 'Krótki opis',
                ],
            ],
            'as app fallback default with no default and another no hidden no unpublished' => [
                'application', LangFallbackType::DEFAULT, null, true, [],
                [
                    'name' => '',
                    'description_html' => '',
                    'description_short' => '',
                ],
            ],
            'as app fallback default with no default and another no hidden unpublished' => [
                'application', LangFallbackType::DEFAULT, null, true, ['products.show_hidden'],
                [
                    'name' => '',
                    'description_html' => '',
                    'description_short' => '',
                ],
            ],
            'as app fallback default with default and another hidden no unpublished' => [
                'application', LangFallbackType::DEFAULT, true, true, ['languages.show_hidden'],
                [
                    'name' => 'Nazwa',
                    'description_html' => 'HTML opis',
                    'description_short' => 'Krótki opis',
                ],
            ],
            'as app fallback default with default and another hidden unpublished' => [
                'application', LangFallbackType::DEFAULT, true, true, ['languages.show_hidden', 'products.show_hidden'],
                [
                    'name' => 'Nazwa',
                    'description_html' => 'HTML opis',
                    'description_short' => 'Krótki opis',
                ],
            ],
            'as app fallback default with default not published and another hidden no unpublished' => [
                'application', LangFallbackType::DEFAULT, false, true, ['languages.show_hidden'],
                [
                    'name' => '',
                    'description_html' => '',
                    'description_short' => '',
                ],
            ],
            'as app fallback default with default not published and another hidden unpublished' => [
                'application', LangFallbackType::DEFAULT, false, true, ['languages.show_hidden', 'products.show_hidden'],
                [
                    'name' => 'Nazwa',
                    'description_html' => 'HTML opis',
                    'description_short' => 'Krótki opis',
                ],
            ],
            'as app fallback default with no default and another hidden no unpublished' => [
                'application', LangFallbackType::DEFAULT, null, true, ['languages.show_hidden'],
                [
                    'name' => '',
                    'description_html' => '',
                    'description_short' => '',
                ],
            ],
            'as app fallback default with no default and another hidden unpublished' => [
                'application', LangFallbackType::DEFAULT, null, true, ['languages.show_hidden', 'products.show_hidden'],
                [
                    'name' => '',
                    'description_html' => '',
                    'description_short' => '',
                ],
            ],
            'as user fallback any with default and another no hidden no unpublished' => [
                'user', LangFallbackType::ANY, true, true, [],
                [
                    'name' => 'Nazwa',
                    'description_html' => 'HTML opis',
                    'description_short' => 'Krótki opis',
                ],
            ],
            'as user fallback any with default and another no hidden unpublished' => [
                'user', LangFallbackType::ANY, true, true, ['products.show_hidden'],
                [
                    'name' => 'Nazwa',
                    'description_html' => 'HTML opis',
                    'description_short' => 'Krótki opis',
                ],
            ],
            'as user fallback any with default not published and another no hidden no unpublished' => [
                'user', LangFallbackType::ANY, false, true, [],
                [
                    'name' => 'Name',
                    'description_html' => 'HTML Beschreibung',
                    'description_short' => 'Kurze Beschreibung',
                ],
            ],
            'as user fallback any with default not published and another no hidden unpublished' => [
                'user', LangFallbackType::ANY, false, true, ['products.show_hidden'],
                [
                    'name' => 'Nazwa',
                    'description_html' => 'HTML opis',
                    'description_short' => 'Krótki opis',
                ],
            ],
            'as user fallback any with no default and another no hidden no unpublished' => [
                'user', LangFallbackType::ANY, null, true, [],
                [
                    'name' => 'Name',
                    'description_html' => 'HTML Beschreibung',
                    'description_short' => 'Kurze Beschreibung',
                ],
            ],
            'as user fallback any with no default and another no hidden unpublished' => [
                'user', LangFallbackType::ANY, null, true, ['products.show_hidden'],
                [
                    'name' => 'Name',
                    'description_html' => 'HTML Beschreibung',
                    'description_short' => 'Kurze Beschreibung',
                ],
            ],
            'as user fallback any with no default and another not published no hidden no unpublished' => [
                'user', LangFallbackType::ANY, null, false, [],
                [
                    'name' => '',
                    'description_html' => '',
                    'description_short' => '',
                ],
            ],
            'as user fallback any with no default and another not published no hidden unpublished' => [
                'user', LangFallbackType::ANY, null, false, ['products.show_hidden'],
                [
                    'name' => 'Name',
                    'description_html' => 'HTML Beschreibung',
                    'description_short' => 'Kurze Beschreibung',
                ],
            ],
            'as user fallback any with default and another hidden no unpublished' => [
                'user', LangFallbackType::ANY, true, true, ['languages.show_hidden'],
                [
                    'name' => 'Nazwa',
                    'description_html' => 'HTML opis',
                    'description_short' => 'Krótki opis',
                ],
            ],
            'as user fallback any with default and another hidden unpublished' => [
                'user', LangFallbackType::ANY, true, true, ['languages.show_hidden', 'products.show_hidden'],
                [
                    'name' => 'Nazwa',
                    'description_html' => 'HTML opis',
                    'description_short' => 'Krótki opis',
                ],
            ],
            'as user fallback any with default not published and another hidden no unpublished' => [
                'user', LangFallbackType::ANY, false, true, ['languages.show_hidden'],
                [
                    'name' => 'Name',
                    'description_html' => 'HTML Beschreibung',
                    'description_short' => 'Kurze Beschreibung',
                ],
            ],
            'as user fallback any with default not published and another hidden unpublished' => [
                'user', LangFallbackType::ANY, false, true, ['languages.show_hidden', 'products.show_hidden'],
                [
                    'name' => 'Nazwa',
                    'description_html' => 'HTML opis',
                    'description_short' => 'Krótki opis',
                ],
            ],
            'as user fallback any with no default and another hidden no unpublished' => [
                'user', LangFallbackType::ANY, null, true, ['languages.show_hidden'],
                [
                    'name' => 'Name',
                    'description_html' => 'HTML Beschreibung',
                    'description_short' => 'Kurze Beschreibung',
                ],
            ],
            'as user fallback any with no default and another hidden unpublished' => [
                'user', LangFallbackType::ANY, null, true, ['languages.show_hidden', 'products.show_hidden'],
                [
                    'name' => 'Name',
                    'description_html' => 'HTML Beschreibung',
                    'description_short' => 'Kurze Beschreibung',
                ],
            ],
            'as user fallback any with no default and another not published hidden no unpublished' => [
                'user', LangFallbackType::ANY, null, false, ['languages.show_hidden'],
                [
                    'name' => '',
                    'description_html' => '',
                    'description_short' => '',
                ],
            ],
            'as user fallback any with no default and another not published hidden unpublished' => [
                'user', LangFallbackType::ANY, null, false, ['languages.show_hidden', 'products.show_hidden'],
                [
                    'name' => 'Name',
                    'description_html' => 'HTML Beschreibung',
                    'description_short' => 'Kurze Beschreibung',
                ],
            ],
            'as app fallback any with default and another no hidden no unpublished' => [
                'application', LangFallbackType::ANY, true, true, [],
                [
                    'name' => 'Nazwa',
                    'description_html' => 'HTML opis',
                    'description_short' => 'Krótki opis',
                ],
            ],
            'as app fallback any with default and another no hidden unpublished' => [
                'application', LangFallbackType::ANY, true, true, ['products.show_hidden'],
                [
                    'name' => 'Nazwa',
                    'description_html' => 'HTML opis',
                    'description_short' => 'Krótki opis',
                ],
            ],
            'as app fallback any with default not published and another no hidden no unpublished' => [
                'application', LangFallbackType::ANY, false, true, [],
                [
                    'name' => 'Name',
                    'description_html' => 'HTML Beschreibung',
                    'description_short' => 'Kurze Beschreibung',
                ],
            ],
            'as app fallback any with default not published and another no hidden unpublished' => [
                'application', LangFallbackType::ANY, false, true, ['products.show_hidden'],
                [
                    'name' => 'Nazwa',
                    'description_html' => 'HTML opis',
                    'description_short' => 'Krótki opis',
                ],
            ],
            'as app fallback any with no default and another no hidden no unpublished' => [
                'application', LangFallbackType::ANY, null, true, [],
                [
                    'name' => 'Name',
                    'description_html' => 'HTML Beschreibung',
                    'description_short' => 'Kurze Beschreibung',
                ],
            ],
            'as app fallback any with no default and another no hidden unpublished' => [
                'application', LangFallbackType::ANY, null, true, ['products.show_hidden'],
                [
                    'name' => 'Name',
                    'description_html' => 'HTML Beschreibung',
                    'description_short' => 'Kurze Beschreibung',
                ],
            ],
            'as app fallback any with no default and another not published no hidden no unpublished' => [
                'application', LangFallbackType::ANY, null, false, [],
                [
                    'name' => '',
                    'description_html' => '',
                    'description_short' => '',
                ],
            ],
            'as app fallback any with no default and another not published no hidden unpublished' => [
                'application', LangFallbackType::ANY, null, false, ['products.show_hidden'],
                [
                    'name' => 'Name',
                    'description_html' => 'HTML Beschreibung',
                    'description_short' => 'Kurze Beschreibung',
                ],
            ],
            'as app fallback any with default and another hidden no unpublished' => [
                'application', LangFallbackType::ANY, true, true, ['languages.show_hidden'],
                [
                    'name' => 'Nazwa',
                    'description_html' => 'HTML opis',
                    'description_short' => 'Krótki opis',
                ],
            ],
            'as app fallback any with default and another hidden unpublished' => [
                'application', LangFallbackType::ANY, true, true, ['languages.show_hidden', 'products.show_hidden'],
                [
                    'name' => 'Nazwa',
                    'description_html' => 'HTML opis',
                    'description_short' => 'Krótki opis',
                ],
            ],
            'as app fallback any with default not published and another hidden no unpublished' => [
                'application', LangFallbackType::ANY, false, true, ['languages.show_hidden'],
                [
                    'name' => 'Name',
                    'description_html' => 'HTML Beschreibung',
                    'description_short' => 'Kurze Beschreibung',
                ],
            ],
            'as app fallback any with default not published and another hidden unpublished' => [
                'application', LangFallbackType::ANY, false, true, ['languages.show_hidden', 'products.show_hidden'],
                [
                    'name' => 'Nazwa',
                    'description_html' => 'HTML opis',
                    'description_short' => 'Krótki opis',
                ],
            ],
            'as app fallback any with no default and another hidden no unpublished' => [
                'application', LangFallbackType::ANY, null, true, ['languages.show_hidden'],
                [
                    'name' => 'Name',
                    'description_html' => 'HTML Beschreibung',
                    'description_short' => 'Kurze Beschreibung',
                ],
            ],
            'as app fallback any with no default and another hidden unpublished' => [
                'application', LangFallbackType::ANY, null, true, ['languages.show_hidden', 'products.show_hidden'],
                [
                    'name' => 'Name',
                    'description_html' => 'HTML Beschreibung',
                    'description_short' => 'Kurze Beschreibung',
                ],
            ],
            'as app fallback any with no default and another not published hidden no unpublished' => [
                'application', LangFallbackType::ANY, null, false, ['languages.show_hidden'],
                [
                    'name' => '',
                    'description_html' => '',
                    'description_short' => '',
                ],
            ],
            'as app fallback any with no default and another not published hidden unpublished' => [
                'application', LangFallbackType::ANY, null, false, ['languages.show_hidden', 'products.show_hidden'],
                [
                    'name' => 'Name',
                    'description_html' => 'HTML Beschreibung',
                    'description_short' => 'Kurze Beschreibung',
                ],
            ],
        ];
    }

    /**
     * @dataProvider unpublishedFallbackDefaultAndAnyProvider
     */
    public function testShowUnpublished($user, $fallback, $default, $another, $show_hidden, $result): void
    {
        $this->$user->givePermissionTo(array_merge(['products.show_details'], $show_hidden));

        $es = Language::create([
            'iso' => 'es',
            'name' => 'Spain',
            'default' => false,
            'hidden' => false,
        ]);

        $de = Language::create([
            'iso' => 'de',
            'name' => 'Deutsch',
            'default' => false,
            'hidden' => false,
        ]);

        $product = null;

        if ($default === true || $default === false) {
            App::setLocale($this->language->getKey());
            $published = $default ? [] : ['published' => []];
            $product = Product::factory()->create([
                    'name' => 'Nazwa',
                    'description_html' => 'HTML opis',
                    'description_short' => 'Krótki opis',
                    'public' => true,
                ] + $published);
        }

        if ($another === true || $another === false) {
            $published = $another ? [] : ['published' => []];
            $data = [
                    'name' => 'Name',
                    'description_html' => 'HTML Beschreibung',
                    'description_short' => 'Kurze Beschreibung',
                    'public' => true,
                ] + $published;
            if ($product !== null) {
                $product->setLocale($de->getKey())->update($data + [
                        'published' => array_merge($product->published, [$de->getKey()]),
                    ]);
            } else {
                App::setLocale($de->getKey());
                $product = Product::factory()->create($data);
            }
        }

        App::setLocale($this->language->getKey());

        $this->actingAs($this->$user)->json('GET', 'products/id:' . $product->getKey(), [
            'lang_fallback' => $fallback,
        ], [
            'Accept-Language' => $es->iso,
        ])
            ->assertOk()
            ->assertJsonFragment($result);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductWithSchemaFallback(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show_details');

        /** @var Language $en */
        $en = Language::create([
            'iso' => 'es',
            'name' => 'English',
            'default' => false,
            'hidden' => false,
        ]);

        /** @var Product $product */
        $product = Product::factory()->create([
            'public' => true,
            'name' => 'Test PL',
            'published' => json_encode([$this->language->getKey(), $en->getKey()]),
        ]);
        $product->setLocale($en->getKey())->fill([
            'name' => 'Test ES',
        ]);
        $product->save();

        $schema = Schema::factory()->create([
            'name' => 'Schemat',
            'type' => SchemaType::SELECT,
            'required' => false,
            'hidden' => false,
        ]);

        $option = Option::factory()->create([
            'name' => 'Opcja 1',
            'schema_id' => $schema->getKey(),
        ]);

        $product->schemas()->save($schema);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/products/id:' . $product->getKey(), headers: ['Accept-Language' => 'es'])
            ->assertOk()
            ->assertJsonFragment([
                'id' => $option->getKey(),
                'name' => 'Opcja 1',
            ])
            ->assertJsonFragment([
                'id' => $schema->getKey(),
                'name' => 'Schemat',
            ])
            ->assertJsonFragment([
                'id' => $product->getKey(),
                'name' => 'Test ES',
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductWithAttributeOptionFallback(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show_details');

        /** @var Language $en */
        $en = Language::create([
            'iso' => 'es',
            'name' => 'English',
            'default' => false,
            'hidden' => false,
        ]);

        /** @var Product $product */
        $product = Product::factory()->create([
            'public' => true,
            'name' => 'Test PL',
            'published' => json_encode([$this->language->getKey(), $en->getKey()]),
        ]);
        $product->setLocale($en->getKey())->fill([
            'name' => 'Test ES',
        ]);
        $product->save();

        $attribute = Attribute::factory()->create([
            'name' => 'Atrybut 1',
            'published' => [$this->lang, $en->getKey()],
        ]);
        $attribute->setLocale($en->getKey())->fill([
            'name' => 'Attribute 1',
        ]);
        $attribute->save();
        $attributeOption = AttributeOption::factory()->create([
            'index' => 1,
            'name' => 'Opcja 1',
            'attribute_id' => $attribute->getKey(),
        ]);

        $product->attributes()->attach($attribute);
        $product->attributes->first()->pivot->options()->attach($attributeOption->getKey());

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/products/id:' . $product->getKey(), headers: ['Accept-Language' => 'es'])
            ->assertOk()
            ->assertJsonFragment([
                'id' => $attributeOption->getKey(),
                'name' => 'Opcja 1',
            ])
            ->assertJsonFragment([
                'id' => $attribute->getKey(),
                'name' => 'Attribute 1',
            ])
            ->assertJsonFragment([
                'id' => $product->getKey(),
                'name' => 'Test ES',
            ]);
    }
}
