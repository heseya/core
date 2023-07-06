<?php

namespace Tests\Feature\Media;

use App\Enums\MediaSource;
use App\Enums\MediaType;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class MediaCreateTest extends TestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testUploadWithSourceExternal(string $user): void
    {
        $this->{$user}->givePermissionTo('media.add');

        $media = [
            'source' => MediaSource::EXTERNAL,
            'type' => MediaType::PHOTO,
            'url' => 'https://example.com/image.png',
        ];

        $this
            ->actingAs($this->{$user})
            ->postJson('/media', $media)
            ->assertCreated();

        $this->assertDatabaseHas('media', $media);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUploadWithUuid(string $user): void
    {
        $this->{$user}->givePermissionTo('media.add');

        $media = [
            'source' => MediaSource::EXTERNAL,
            'type' => MediaType::PHOTO,
            'url' => 'https://example.com/image.png',
            'id' => Uuid::uuid4()->toString(),
        ];

        $this
            ->actingAs($this->{$user})
            ->postJson('/media', $media)
            ->assertCreated();

        $this->assertDatabaseHas('media', $media);
    }
}
