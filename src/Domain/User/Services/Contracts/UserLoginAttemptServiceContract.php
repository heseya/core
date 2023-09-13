<?php

namespace Domain\User\Services\Contracts;

interface UserLoginAttemptServiceContract
{
    public function store(bool $logged = false): void;
}
