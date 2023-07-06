<?php

use App\Enums\MediaAttachmentType;
use App\Enums\VisibilityType;
use App\Models\Media;
use App\Models\MediaAttachment;
use App\Models\Product;
use Tests\TestCase;

class ProductAttachmentsTest extends TestCase
{
    protected Product $product;
    protected Media $media;

    public function setUp(): void
    {
        parent::setUp();

        $this->product = Product::factory()->create([
            'public' => true,
        ]);

        $this->media = Media::factory()->create();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowProductsWithAttachments(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show_details');

        $this->showProductsWithAttachments($this->{$user}, VisibilityType::PUBLIC);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductsWithAttachmentsPrivate(string $user): void
    {
        $this->{$user}->givePermissionTo(['products.show_details', 'products.show_attachments_private']);

        $this->showProductsWithAttachments($this->{$user}, VisibilityType::PRIVATE);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductsWithAttachmentsPrivateNoPermissions(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show_details');

        $this->createAttachment(VisibilityType::PRIVATE);

        $this
            ->actingAs($this->{$user})
            ->getJson("/products/id:{$this->product->getKey()}")
            ->assertOk()
            ->assertJsonCount(0, 'data.attachments');
    }

    public function testAddAttachmentUnauthorized(): void
    {
        $this
            ->postJson("/products/id:{$this->product->getKey()}/attachments")
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testAddAttachment(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        $data = [
            'name' => 'Test',
            'type' => MediaAttachmentType::OTHER->value,
            'visibility' => VisibilityType::PUBLIC->value,
            'description' => null,
        ];

        $this->addAttachment($this->{$user}, $data);
    }

    /**
     * @dataProvider authProvider
     */
    public function testAddAttachmentWithoutDescription(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        $data = [
            'name' => 'Test',
            'type' => MediaAttachmentType::OTHER->value,
            'visibility' => VisibilityType::PUBLIC->value,
        ];

        $this->addAttachment($this->{$user}, $data);
    }

    /**
     * @dataProvider authProvider
     */
    public function testAddAttachmentWithNullDescription(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        $data = [
            'name' => 'Test',
            'type' => MediaAttachmentType::OTHER->value,
            'visibility' => VisibilityType::PUBLIC->value,
            'description' => null,
        ];

        $this->addAttachment($this->{$user}, $data);
    }

    public function testEditAttachmentUnauthorized(): void
    {
        $attachment = $this->createAttachment(VisibilityType::PUBLIC);

        $this
            ->patchJson("/products/id:{$this->product->getKey()}/attachments/id:{$attachment->getKey()}")
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testEditAttachment(string $user): void
    {
        $this->{$user}->givePermissionTo(['products.edit', 'products.show_attachments_private']);

        $data = [
            'name' => 'Test updated',
            'type' => MediaAttachmentType::INVOICE->value,
            'visibility' => VisibilityType::PRIVATE->value,
            'description' => 'test-updated',
        ];

        $this->editAttachment($this->{$user}, $data, $data);
    }

    /**
     * @dataProvider authProvider
     */
    public function testEditAttachmentNullDescription(string $user): void
    {
        $this->{$user}->givePermissionTo(['products.edit', 'products.show_attachments_private']);

        $data = [
            'description' => null,
        ];

        $this->editAttachment($this->{$user}, $data, $data);
    }

    /**
     * @dataProvider authProvider
     */
    public function testEditAttachmentNoDescription(string $user): void
    {
        $this->{$user}->givePermissionTo(['products.edit', 'products.show_attachments_private']);

        $initialData = [
            'description' => 'persisted',
        ];

        $this->editAttachment($this->{$user}, [], $initialData, $initialData);
    }

    public function testDeleteAttachmentUnauthorized(): void
    {
        $attachment = $this->createAttachment(VisibilityType::PUBLIC);

        $this
            ->deleteJson("/products/id:{$this->product->getKey()}/attachments/id:{$attachment->getKey()}")
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteAttachment(string $user): void
    {
        $this->{$user}->givePermissionTo(['products.edit', 'products.show_attachments_private']);

        $attachment = $this->createAttachment(VisibilityType::PUBLIC);

        $this
            ->actingAs($this->{$user})
            ->deleteJson("/products/id:{$this->product->getKey()}/attachments/id:{$attachment->getKey()}")
            ->assertNoContent();

        $this->assertModelMissing($attachment);
        $this->assertModelMissing($this->media);
    }

    private function createAttachment(VisibilityType $visibility)
    {
        return MediaAttachment::query()->create([
            'name' => 'Test',
            'type' => MediaAttachmentType::OTHER,
            'visibility' => $visibility,
            'description' => 'test',
            'media_id' => $this->media->getKey(),
            'model_id' => $this->product->getKey(),
            'model_type' => Product::class,
        ]);
    }

    private function showProductsWithAttachments($user, VisibilityType $visibility): void
    {
        $attachment = $this->createAttachment($visibility);

        $this
            ->actingAs($user)
            ->getJson("/products/id:{$this->product->getKey()}")
            ->assertOk()
            ->assertJson([
                'data' => [
                    'attachments' => [
                        [
                            'id' => $attachment->getKey(),
                            'name' => $attachment->name,
                            'type' => $attachment->type->value,
                            'description' => $attachment->description,
                            'visibility' => $attachment->visibility->value,
                            'media' => [
                                'id' => $this->media->getKey(),
                                'type' => $this->media->type->value,
                                'url' => $this->media->url,
                                'slug' => $this->media->slug,
                                'alt' => $this->media->alt,
                            ],
                        ],
                    ],
                ],
            ]);
    }

    private function addAttachment($user, array $data): void
    {
        $this
            ->actingAs($user)
            ->postJson("/products/id:{$this->product->getKey()}/attachments", $data + [
                'media_id' => $this->media->getKey(),
            ])
            ->assertCreated()
            ->assertJson([
                'data' => $data,
            ]);

        $this->assertDatabaseHas('media_attachments', $data + [
            'media_id' => $this->media->getKey(),
            'model_id' => $this->product->getKey(),
            'model_type' => Product::class,
        ]);
    }

    private function editAttachment($user, array $inputData, array $outputData = [], array $initialData = []): void
    {
        $createData = $initialData + [
            'name' => 'Test',
            'type' => MediaAttachmentType::OTHER->value,
            'visibility' => VisibilityType::PUBLIC->value,
            'description' => 'test',
        ];

        $attachment = MediaAttachment::query()->create($createData + [
            'media_id' => $this->media->getKey(),
            'model_id' => $this->product->getKey(),
            'model_type' => Product::class,
        ]);

        $finalData = $outputData + $createData;

        $this
            ->actingAs($user)
            ->patchJson("/products/id:{$this->product->getKey()}/attachments/id:{$attachment->getKey()}", $inputData)
            ->assertOk()
            ->assertJson([
                'data' => $finalData,
            ]);

        $this->assertDatabaseHas('media_attachments', $finalData + [
            'media_id' => $this->media->getKey(),
            'model_id' => $this->product->getKey(),
            'model_type' => Product::class,
        ]);
    }
}
