<?php

namespace Heseya\Dto\Tests;

use Heseya\Dto\Dto;
use Heseya\Dto\Hidden;
use Heseya\Dto\Missing;

class TestHiddenDto extends Dto
{
    #[Hidden]
    private ?string $name;
    private string|null|Missing $description;

    /**
     * @return string|null
     */
    public function getName(): ?string
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
