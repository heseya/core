<?php

namespace Tests\Unit;

use App\Http\Resources\Schemas\TextSchemaResource;
use App\Schemas\TextSchema;
use PHPUnit\Framework\TestCase;

class SchemaTest extends TestCase
{
    public function testTableName(): void
    {
        $schema = new TextSchema;

        $this->assertEquals('schemas_text', $schema->getTable());
    }

    public function testResourceName(): void
    {
        $schema = new TextSchema;

        $this->assertEquals(TextSchemaResource::class, $schema->getResource());
    }

    public function testResourceCast(): void
    {
        $schema = new TextSchema;

        $this->assertInstanceOf(TextSchemaResource::class, $schema->toResource());
    }
}
