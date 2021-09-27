<?php

namespace Heseya\Dto\Tests\Unit;

use Heseya\Dto\Tests\RoleDto;
use Illuminate\Foundation\Testing\TestCase;
use Tests\CreatesApplication;

class DtoTest extends TestCase
{
    use CreatesApplication;

    public function testOptionalParams(): void
    {
        $dto = new RoleDto(description: 'super description');

        dd([
            $dto->getName(),
            $dto->getDescription(),
            $dto->toArray()
        ]);
    }
}
