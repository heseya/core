<?php

namespace App\Services\Contracts;

use App\Dtos\StatusDto;
use App\Models\Status;

interface StatusServiceContract
{
    public function store(StatusDto $dto): Status;

    public function update(Status $status, StatusDto $dto): Status;

    public function destroy(Status $status): void;
}
