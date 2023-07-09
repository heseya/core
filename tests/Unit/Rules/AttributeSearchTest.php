<?php

namespace Tests\Unit\Rules;

use App\Rules\AttributeSearch;
use Tests\UnitTestCase;

class AttributeSearchTest extends UnitTestCase
{
    protected AttributeSearch $rule;

    public function setUp(): void
    {
        parent::setUp();

        $this->rule = new AttributeSearch();
    }

    public function testValidateMinMaxPassMin(): void
    {
        $this->assertTrue($this->callMethod(
            $this->rule,
            'validateMinMax',
            [
                'value' => [
                    'min' => 1,
                ],
            ]
        ));
    }

    public function testValidateMinMaxPassMax(): void
    {
        $this->assertTrue($this->callMethod($this->rule, 'validateMinMax', [
            'value' => [
                'max' => 1,
            ],
        ]));
    }

    public function testValidateMinMaxDontPassEmpty(): void
    {
        $this->assertFalse($this->callMethod($this->rule, 'validateMinMax', [
            'value' => [],
        ]));
    }

    public function testValidateNumberPassNumeric(): void
    {
        $this->assertTrue($this->callMethod(
            $this->rule,
            'validateNumber',
            [
                'value' => [
                    'min' => 1,
                    'max' => '100',
                ],
            ],
        ));
    }

    public function testValidateNumberDontPassString(): void
    {
        $this->assertFalse($this->callMethod($this->rule, 'validateNumber', [
            'value' => [
                'max' => 'test test',
            ],
        ]));
    }

    // TODO: mock model find
    //    public function testValidateOptionsPassUuid(): void
    //    {
    //        $this->assertTrue($this->callMethod($this->rule, 'validateOptions', [
    //            'value' => 'f9f96c73-1e19-4e66-a232-f473b29edd63,f9f96c73-1e19-4e66-a232-f473b29edd63',
    //        ]));
    //    }

    public function testValidateOptionsDontPassNonUuid(): void
    {
        $this->assertFalse($this->callMethod($this->rule, 'validateOptions', [
            'value' => 'f9f96c73-1e19-4e66-a232-f473b29edd63,test',
        ]));
    }
}
