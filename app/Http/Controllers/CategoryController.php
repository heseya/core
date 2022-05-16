<?php

namespace App\Http\Controllers;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ServerException;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class CategoryController extends Controller
{
    public function index(string $lang): JsonResource
    {
        $response = Http::get('https://www.google.com/basepages/producttype/taxonomy-with-ids.' . $lang . '.txt');

        if ($response->failed()) {
            throw new ServerException(Exceptions::SERVER_ERROR);
        }

        $data = explode("\n", $response->body());

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
        return CategoryResource::collection($collection);
    }
}
