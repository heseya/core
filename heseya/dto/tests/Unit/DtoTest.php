<?php

namespace Heseya\Dto\Tests\Unit;

use Heseya\Dto\DtoException;
use Heseya\Dto\Missing;
use Heseya\Dto\Tests\TestDto;
use Heseya\Dto\Tests\TestHiddenDto;
use Illuminate\Foundation\Testing\TestCase;
use Tests\CreatesApplication;
use TypeError;

class DtoTest extends TestCase
{
    use CreatesApplication;

    public function testAllParamPresent(): void
    {
        $dto = new TestDto(
            name: 'test name',
            description: 'test description',
        );

        $this->assertEquals([
            'name' => 'test name',
            'description' => 'test description',
        ], $dto->toArray());
    }

    public function testRequiredParamMissing(): void
    {
        $this->expectException(DtoException::class);

        new TestDto(description: 'test description');
    }

    public function testOptionalParamMissing(): void
    {
        $dto = new TestDto(name: 'test name');

        $this->assertEquals(['name' => 'test name'], $dto->toArray());
    }

    public function testOptionalParamMissingGetter(): void
    {
        $dto = new TestDto(name: 'test name');

        $this->assertInstanceOf(Missing::class, $dto->getDescription());
    }

    public function testNonNullableParam(): void
    {
        $this->expectException(TypeError::class);

        new TestDto(name: null);
    }

    public function testHiddenParam(): void
    {
        $dto = new TestHiddenDto(
            name: 'test name',
            description: 'test description',
        );

        $this->assertEquals([
            'description' => 'test description',
        ], $dto->toArray());
    }
}
