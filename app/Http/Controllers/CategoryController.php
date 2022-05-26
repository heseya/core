<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Services\Contracts\CategoryServiceContract;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class CategoryController extends Controller
{
    public function __construct(private CategoryServiceContract $categoryService)
    {
    }

    public function index(string $lang): JsonResource
    {
        $data = $this->categoryService->getGoogleProductCategory($lang);

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
