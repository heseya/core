<?php

namespace App\Services\Contracts;

use App\Models\Schema;
use Domain\ProductSchema\Dtos\SchemaDto;

interface SchemaCrudServiceContract
{
    public function store(SchemaDto $dto): Schema;

    public function update(Schema $schema, SchemaDto $dto): Schema;

    public function destroy(Schema $schema): void;
}
