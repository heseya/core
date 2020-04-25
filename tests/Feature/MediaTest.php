<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MediaTest extends TestCase
{
    /**
     * @return void
     */
    public function testUploadJpg()
    {
        $file = UploadedFile::fake()->image('image.jpg');

        $this->upload($file);
    }

    /**
     * @return void
     */
    public function testUploadJpeg()
    {
        $file = UploadedFile::fake()->image('image.jpeg');

        $this->upload($file);
    }

    /**
     * @return void
     */
    public function testUploadPng()
    {
        $file = UploadedFile::fake()->image('image.png');

        $this->upload($file);
    }

    /**
     * @return void
     */
    public function testUploadGif()
    {
        $file = UploadedFile::fake()->image('image.gif');

        $this->upload($file);
    }

    /**
     * @return void
     */
    public function testUploadBmp()
    {
        $file = UploadedFile::fake()->image('image.bmp');

        $this->upload($file);
    }

    /**
     * @return void
     */
    protected function upload($file)
    {
        $response = $this->post('/media', [
            'file' => $file,
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonStructure(['data' => [
                'id',
                'type',
                'url',
            ]]);
    }
}
