<?php

namespace App\Services;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use App\Exceptions\ServerException;
use App\Services\Contracts\SilverboxServiceContract;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final readonly class SilverboxService implements SilverboxServiceContract
{
    public function upload(UploadedFile $file, bool $private): string
    {
        $private = $private ? '?private' : '';

        /** @var Response $response */
        $response = Http::attach('file', $file->getContent(), 'file')
            ->withHeaders(['x-api-key' => Config::get('silverbox.key')])
            ->post(Config::get('silverbox.host') . '/' . Config::get('silverbox.client') . $private);

        if ($response->failed()) {
            throw new ServerException(
                message: Exceptions::SERVER_CDN_ERROR,
                errorArray: $response->json(),
            );
        }

        return Config::get('silverbox.host') . '/' . $response->json('0.path');
    }

    public function updateSlug(string $url, ?string $slug): string
    {
        if (!Str::contains($url, Config::get('silverbox.host'))) {
            throw new ClientException(message: Exceptions::CDN_NOT_ALLOWED_TO_CHANGE_ALT);
        }

        /** @var Response $response */
        $response = Http::asJson()
            ->acceptJson()
            ->withHeaders(['x-api-key' => Config::get('silverbox.key')])
            ->patch($url, [
                'slug' => $slug,
            ]);

        if ($response->failed() || !isset($response['path'])) {
            throw new ServerException(
                message: Exceptions::SERVER_CDN_ERROR,
                errorArray: $response->json() ?? [],
            );
        }

        return Config::get('silverbox.host') . '/' . $response['path'];
    }

    public function delete(string $url): void
    {
        if ($this->isSilverbox($url)) {
            Http::withHeaders(['x-api-key' => Config::get('silverbox.key')])->delete($url);
        }
    }

    private function isSilverbox(string $url): bool
    {
        return Str::contains($url, Config::get('silverbox.host'));
    }
}
