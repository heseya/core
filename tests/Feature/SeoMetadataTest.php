<?php

use App\Models\SeoMetadata;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class SeoMetadataTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testShowUnauthorized(): void
    {
        $response = $this->json('GET', '/seo');
        $response->assertForbidden();
    }

    public function testShow(): void
    {
        $this->user->givePermissionTo('seo.show');

        $seo = SeoMetadata::factory()->create([
            'global' => true
        ]);

        $response = $this->actingAs($this->user)->json('GET', '/seo');

        $response->assertOk()->assertJsonFragment([
            'title' => $seo->title,
            'description' => $seo->description,
        ]);
    }

    public function testCreateUnauthorized(): void
    {
        $seo = [
            'title' => 'title',
            'description' => 'description',
        ];
        $response = $this->json('PATCH', '/seo', $seo);
        $response->assertForbidden();
    }

    public function testCreateWithoutGlobalShow(): void
    {
        $this->user->givePermissionTo('seo.edit');

        $seo = [
            'title' => 'title',
            'description' => 'description',
            'keywords' => ['key', 'words'],
            'twitter_card' => 'summary',
        ];
        $response = $this->actingAs($this->user)->json('PATCH', '/seo', $seo);

        $response->assertJson(fn (AssertableJson $json) =>
            $json->has('meta', fn ($json) =>
                    $json->missing('seo')
                        ->etc())
                ->has('data', fn ($json) =>
                    $json->where('title', $seo['title'])
                        ->where('description', $seo['description'])
                        ->etc())
                ->etc()
        );

        $seo = SeoMetadata::where('global', '=', true)->first();

        $this->assertEquals(['key', 'words'], $seo->keywords);

        $this->assertDatabaseHas('seo_metadata', [
            'title' => $seo->title,
            'description' => $seo->description,
            'global' => true,
            'twitter_card' => $seo->twitter_card,
        ]);
    }

    public function testCreateWithGlobalShow(): void
    {
        $this->user->givePermissionTo(['seo.edit', 'seo.show']);

        $seo = [
            'title' => 'title',
            'description' => 'description',
            'keywords' => ['key', 'words'],
        ];
        $response = $this->actingAs($this->user)->json('PATCH', '/seo', $seo);

        $response->assertJson(fn (AssertableJson $json) =>
            $json->has('meta', fn ($json) =>
                    $json->has('seo')
                        ->etc())
                ->has('data', fn ($json) =>
                    $json->where('title', $seo['title'])
                        ->where('description', $seo['description'])
                        ->etc())
                ->etc()
        );


        $seo = SeoMetadata::where('global', '=', true)->first();

        $this->assertEquals(['key', 'words'], $seo->keywords);

        $this->assertDatabaseHas('seo_metadata', [
            'title' => $seo->title,
            'description' => $seo->description,
            'global' => true,
        ]);
    }

    public function testUpdateGlobal(): void
    {
        $this->user->givePermissionTo('seo.edit');

        $seo1 = SeoMetadata::factory()->create([
            'global' => true,
        ]);

        $seo2 = [
            'title' => 'title',
            'description' => 'description',
            'keywords' => ['key', 'words'],
        ];
        $response = $this->actingAs($this->user)->json('PATCH', '/seo', $seo2);

        $response->assertOk()
            ->assertJsonFragment([
                'title' => $seo2['title'],
                'description' => $seo2['description'],
            ]);

        $seo2 = SeoMetadata::where('global', '=', true)->first();

        $this->assertEquals(['key', 'words'], $seo2->keywords);

        $this->assertDatabaseCount('seo_metadata', 1);

        $this->assertDatabaseHas('seo_metadata', [
            'title' => $seo2->title,
            'description' => $seo2->description,
            'global' => true,
        ]);
    }
}
