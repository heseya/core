<?php

namespace Tests\Unit;

use App\Enums\SchemaType;
use App\Models\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchemaValidationTest extends TestCase
{
    use RefreshDatabase;

    public function testValidateSelectSchemaOptional(): void
    {
        /** @var Schema $schema */
        $schema = Schema::factory()->create([
            'type' => SchemaType::select,
            'required' => false,
        ]);

        $this->assertNull(
            $schema->validate(null, 1),
        );
    }
}
