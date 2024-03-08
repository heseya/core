<?php

namespace Tests\Feature\Attributes;

use Domain\ProductAttribute\Enums\AttributeType;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;
use Tests\TestCase;

class AttributeTestCase extends TestCase
{
    protected Attribute $attribute;
    protected array $attributeData;

    public function setUp(): void
    {
        parent::setUp();

        $this->attribute = Attribute::factory()->create([
            'global' => true,
            'sortable' => true,
            'type' => AttributeType::SINGLE_OPTION,
        ]);

        $this->attributeData = [
            'name' => 'new attribute',
            'slug' => 'new-attribute',
            'description' => 'lorem ipsum',
            'type' => AttributeType::SINGLE_OPTION,
            'global' => false,
            'sortable' => true,
            'published' => [$this->lang],
        ];
    }
}
