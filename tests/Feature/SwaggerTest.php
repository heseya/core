<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SwaggerTest extends TestCase
{
    public function testGenerateDocs(): void
    {
        $path = __DIR__ . '/../../docs/api.json';

        if (file_exists($path)) {
            unlink($path);
        }

        $this->assertFileDoesNotExist($path);

        Artisan::call('l5-swagger:generate');

        $this->assertFileExists($path);
    }
}
