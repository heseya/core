<?php

namespace Tests\Feature\Attributes;

use Domain\ProductAttribute\Enums\AttributeType;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;
use Tests\TestCase;

abstract class AttributeOptionTestCase extends TestCase
{
    protected Attribute $attribute;
    protected AttributeOption $option;
    /** @var array<string, mixed> */
    protected array $newOption;
    /** @var array<string, mixed> */
    protected array $optionData;

    public function setUp(): void
    {
        parent::setUp();

        $this->attribute = Attribute::factory()->create([
            'global' => true,
            'sortable' => true,
            'type' => AttributeType::SINGLE_OPTION,
        ]);

        $this->option = AttributeOption::factory()->create([
            'index' => 1,
            'attribute_id' => $this->attribute->getKey(),
        ]);

        $this->attribute->refresh();

        $this->optionData = [
            'value_number' => null,
            'value_date' => '2023-08-09'
        ];
        $this->newOption = array_merge($this->optionData, [
            'translations' => [
                $this->lang => [
                    'name' => 'new option',
                ],
            ],
            'published' => [
                $this->lang,
            ],
        ]);
        $this->optionData['name'] = 'new option';
    }
}
