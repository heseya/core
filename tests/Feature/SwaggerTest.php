<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SwaggerTest extends TestCase
{
    private string $path;

    public function setUp(): void
    {
        parent::setUp();

        $this->path = __DIR__ . '/../../docs/api.json';

        if (file_exists($this->path)) {
            unlink($this->path);
        }
    }

    /**
     * @return void
     */
    public function testGenerateDocs()
    {
        Artisan::call('l5-swagger:generate');

        $this->assertTrue(file_exists($this->path));
    }
}
