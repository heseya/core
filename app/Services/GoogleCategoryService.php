<?php

namespace App\Services;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\GoogleProductCategoryFileException;
use App\Exceptions\ServerException;
use App\Services\Contracts\GoogleCategoryServiceContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class GoogleCategoryService implements GoogleCategoryServiceContract
{
    public const FILE_NAME = 'google_product_category_';

    /**
     * @throws GoogleProductCategoryFileException
     * @throws ServerException
     */
    public function getGoogleProductCategory(string $lang, bool $force = false): Collection
    {
        $path = $this->path($lang);

        if ($force || !Storage::exists($path)) {
            $data = $this->getFromGoogleServer($lang);
            file_put_contents($path, $data);
        }

        $data ??= $this->getGoogleProductCategoryFileContent($lang);

        # Removing google header from text and last empty line then reindexing.
        unset($data[0]);
        array_pop($data);
        $data = array_values($data);

        $collection = Collection::make();

        foreach ($data as $row) {
            $rowData = explode(' - ', $row);
            $collection->push([
                'id' => (int) $rowData[0],
                'name' => $rowData[1],
            ]);
        }

        return $collection;
    }

    /**
     * @throws GoogleProductCategoryFileException
     */
    private function getGoogleProductCategoryFileContent(string $lang): array
    {
        $path = $this->path($lang);

        if (!Storage::exists($path)) {
            throw new GoogleProductCategoryFileException();
        }

        return file($path);
    }

    /**
     * @throws ServerException
     */
    private function getFromGoogleServer(string $lang): array
    {
        $response = Http::get('https://www.google.com/basepages/producttype/taxonomy-with-ids.' . $lang . '.txt');

        if ($response->failed()) {
            throw new ServerException(Exceptions::SERVER_ERROR);
        }

        return explode("\n", $response->body());
    }

    private function path(string $lang): string
    {
        return Storage::path(self::FILE_NAME . $lang . '.txt');
    }
}
