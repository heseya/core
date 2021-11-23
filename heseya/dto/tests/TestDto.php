<?php

namespace Heseya\Dto\Tests;

use Heseya\Dto\Dto;
use Heseya\Dto\Missing;

class TestDto extends Dto
{
    private string $name;
    private string|null|Missing $description;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Missing|string|null
     */
    public function getDescription(): Missing|string|null
    {
        return $this->description;
    }
}
