<?php

namespace Tests\Feature\Media;

use App\Enums\MediaSource;
use App\Enums\MediaType;
use Tests\TestCase;

class MediaCreateTest extends TestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testUploadWithSourceExternal(string $user): void
    {
        $this->$user->givePermissionTo('media.add');

        $url = 'https://example.com/image.png';

        $this
            ->actingAs($this->$user)
            ->postJson('/media', [
                'source' => MediaSource::EXTERNAL,
                'type' => MediaType::PHOTO,
                'url' => $url,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('media', [
            'source' => MediaSource::EXTERNAL,
            'type' => MediaType::PHOTO,
            'url' => $url,
        ]);
    }
}
