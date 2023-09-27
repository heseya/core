<?php

declare(strict_types=1);

namespace Domain\User\Services\Contracts;

interface UserLoginAttemptServiceContract
{
    public function store(bool $logged = false): void;
}
