<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Laravel\Passport\Passport;
use Tests\TestCase;

class MediaTest extends TestCase
{
    public function testUploadUnauthorized()
    {
        $response = $this->postJson('/media');
        $response->assertUnauthorized();
    }

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
     * @param UploadedFile $file
     *
     * @return void
     */
    private function upload(UploadedFile $file)
    {
        Passport::actingAs($this->user);

        $response = $this->postJson('/media', [
            'file' => $file,
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure(['data' => [
                'id',
                'type',
                'url',
            ]]);
    }
}
