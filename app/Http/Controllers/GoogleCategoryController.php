<?php

namespace App\Http\Controllers;

use App\Enums\GoogleCategoriesLang;
use App\Http\Resources\CategoryResource;
use App\Services\Contracts\GoogleCategoryServiceContract;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Validator;

class GoogleCategoryController extends Controller
{
    public function __construct(private GoogleCategoryServiceContract $categoryService)
    {
    }

    public function index(string $lang): JsonResource
    {
        Validator::make(['lang' => $lang], [
            'lang' => ['string', 'required', new EnumValue(GoogleCategoriesLang::class)],
        ])->validate();

        return CategoryResource::collection(
            $this->categoryService->getGoogleProductCategory($lang),
        );
    }
}
