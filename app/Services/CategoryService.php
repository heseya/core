<?php

namespace App\Services;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\GoogleProductCategoryFileException;
use App\Exceptions\ServerException;
use App\Services\Contracts\CategoryServiceContract;
use Illuminate\Support\Facades\Http;

class CategoryService implements CategoryServiceContract
{
    public const FILE_NAME = 'google_product_category_';

    /**
     * @throws GoogleProductCategoryFileException
     * @throws ServerException
     */
    public function getGoogleProductCategory(string $lang = 'en-US', bool $force = false): array
    {
        $path = resource_path('storage/' . self::FILE_NAME . $lang . '.txt');

        if ($force || !file_exists($path)) {
            $data = $this->getFromGoogleServer($lang);
            file_put_contents($path, $data);
        }

        return $data ?? $this->getGoogleProductCategoryFileContent($lang);
    }

    /**
     * @throws GoogleProductCategoryFileException
     */
    public function getGoogleProductCategoryFileContent(string $lang = 'en-US'): array
    {
        $path = resource_path('storage/' . self::FILE_NAME . $lang . '.txt');

        if (!file_exists($path)) {
            throw new GoogleProductCategoryFileException();
        }

        return file($path);
    }

    /**
     * @throws ServerException
     */
    public function getFromGoogleServer(string $lang = 'en-US'): array
    {
        $response = Http::get('https://www.google.com/basepages/producttype/taxonomy-with-ids.' . $lang . '.txt');

        if ($response->failed()) {
            throw new ServerException(Exceptions::SERVER_ERROR);
        }

        return explode("\n", $response->body());
    }
}
