<?php

namespace App\Services\Contracts;

use App\Dtos\SchemaDto;
use App\Models\Schema;

interface SchemaCrudServiceContract
{
    public function store(SchemaDto $dto): Schema;

    public function update(Schema $schema, SchemaDto $dto): Schema;

    public function destroy(Schema $schema): void;
}
