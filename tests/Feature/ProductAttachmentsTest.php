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
        $this->$user->givePermissionTo('products.show_details');

        $this->showProductsWithAttachments($this->$user, VisibilityType::PUBLIC);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductsWithAttachmentsPrivate(string $user): void
    {
        $this->$user->givePermissionTo(['products.show_details', 'products.show_private_attachments']);

        $this->showProductsWithAttachments($this->$user, VisibilityType::PRIVATE);
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
        $this->$user->givePermissionTo('products.edit');

        $data = [
            'name' => 'Test',
            'type' => MediaAttachmentType::OTHER->value,
            'visibility' => VisibilityType::PUBLIC->value,
            'label' => null,
        ];

        $this->addAttachment($this->$user, $data);
    }

    /**
     * @dataProvider authProvider
     */
    public function testAddAttachmentWithoutLabel(string $user): void
    {
        $this->$user->givePermissionTo('products.edit');

        $data = [
            'name' => 'Test',
            'type' => MediaAttachmentType::OTHER->value,
            'visibility' => VisibilityType::PUBLIC->value,
        ];

        $this->addAttachment($this->$user, $data);
    }

    /**
     * @dataProvider authProvider
     */
    public function testAddAttachmentWithNullLabel(string $user): void
    {
        $this->$user->givePermissionTo('products.edit');

        $data = [
            'name' => 'Test',
            'type' => MediaAttachmentType::OTHER->value,
            'visibility' => VisibilityType::PUBLIC->value,
            'label' => null,
        ];

        $this->addAttachment($this->$user, $data);
    }

    public function testEditAttachmentUnauthorized(): void
    {
        $attachment = MediaAttachment::query()->create( [
            'name' => 'Test',
            'type' => MediaAttachmentType::OTHER,
            'visibility' => VisibilityType::PUBLIC,
            'label' => 'test',
            'media_id' => $this->media->getKey(),
            'model_id' => $this->product->getKey(),
            'model_type' => Product::class,
        ]);

        $this
            ->patchJson("/products/id:{$this->product->getKey()}/attachments/id:{$attachment->getKey()}")
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testEditAttachment(string $user): void
{
        $this->$user->givePermissionTo(['products.edit', 'products.show_private_attachments']);

        $data = [
            'name' => 'Test updated',
            'type' => MediaAttachmentType::INVOICE->value,
            'visibility' => VisibilityType::PRIVATE->value,
            'label' => 'test-updated',
        ];

        $this->editAttachment($this->$user, $data, $data);
    }

    /**
     * @dataProvider authProvider
     */
    public function testEditAttachmentNullLabel(string $user): void
    {
        $this->$user->givePermissionTo(['products.edit', 'products.show_private_attachments']);

        $data = [
            'label' => null,
        ];

        $this->editAttachment($this->$user, $data, $data);
    }

    /**
     * @dataProvider authProvider
     */
    public function testEditAttachmentNoLabel(string $user): void
    {
        $this->$user->givePermissionTo(['products.edit', 'products.show_private_attachments']);

        $initialData = [
            'label' => 'persisted',
        ];

        $this->editAttachment($this->$user, [], $initialData, $initialData);
    }

    public function testDeleteAttachmentUnauthorized(): void
    {
        $attachment = MediaAttachment::query()->create( [
            'name' => 'Test',
            'type' => MediaAttachmentType::OTHER,
            'visibility' => VisibilityType::PUBLIC,
            'label' => 'test',
            'media_id' => $this->media->getKey(),
            'model_id' => $this->product->getKey(),
            'model_type' => Product::class,
        ]);

        $this
            ->deleteJson("/products/id:{$this->product->getKey()}/attachments/id:{$attachment->getKey()}")
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteAttachment(string $user): void
    {
        $this->$user->givePermissionTo(['products.edit', 'products.show_private_attachments']);

        $attachment = MediaAttachment::query()->create( [
            'name' => 'Test',
            'type' => MediaAttachmentType::OTHER,
            'visibility' => VisibilityType::PUBLIC,
            'label' => 'test',
            'media_id' => $this->media->getKey(),
            'model_id' => $this->product->getKey(),
            'model_type' => Product::class,
        ]);

        $this
            ->actingAs($this->$user)
            ->deleteJson("/products/id:{$this->product->getKey()}/attachments/id:{$attachment->getKey()}")
            ->assertNoContent();

        $this->assertModelMissing($attachment);
        $this->assertModelMissing($this->media);
    }

    private function showProductsWithAttachments($user, VisibilityType $visibility): void
    {
        $attachment = MediaAttachment::query()->create([
            'name' => 'Test',
            'type' => MediaAttachmentType::OTHER,
            'visibility' => $visibility,
            'label' => 'test',
            'media_id' => $this->media->getKey(),
            'model_id' => $this->product->getKey(),
            'model_type' => Product::class,
        ]);

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
                            'label' => $attachment->label,
                            'visibility' => $attachment->visibility->value,
                            'media' => [
                                'id' => $this->media->getKey(),
                                'type' => $this->media->type->value,
                                'url' => $this->media->url,
                                'slug' => $this->media->slug,
                                'alt' => $this->media->alt,
                            ]
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
            'label' => 'test',
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
