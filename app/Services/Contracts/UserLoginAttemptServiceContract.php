<?php

namespace App\Services\Contracts;

interface UserLoginAttemptServiceContract
{
    public function store(bool $logged = false): void;
}
