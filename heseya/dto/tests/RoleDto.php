<?php

namespace Heseya\Dto\Tests;

use Heseya\Dto\Dto;
use Heseya\Dto\Missing;

class RoleDto extends Dto
{
    private string|null|Missing $name;
    #[Hidden]
    private string|null|Missing $description;

    /**
     * @return Missing|string|null
     */
    public function getDescription(): string|Missing|null
    {
        return $this->description;
    }

    /**
     * @return Missing|string|null
     */
    public function getName(): string|Missing|null
    {
        return $this->name;
    }
}

//    /**
//     * @param string|null|Missing $name
//     * @param string|null|Missing $description
//     */
//    public function __construct(...$data)
//    {
//        parent::__construct(...$data);
//    }
