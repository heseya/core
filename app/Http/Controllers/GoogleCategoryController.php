<?php

namespace App\Http\Controllers;

use App\Enums\GoogleCategoriesLang;
use App\Http\Resources\CategoryResource;
use App\Services\Contracts\GoogleCategoryServiceContract;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;

class GoogleCategoryController extends Controller
{
    public function __construct(
        private readonly GoogleCategoryServiceContract $categoryService
    ) {}

    public function index(string $lang): JsonResource
    {
        Validator::make(['lang' => $lang], [
            'lang' => ['string', 'required', new Enum(GoogleCategoriesLang::class)],
        ])->validate();

        return CategoryResource::collection(
            $this->categoryService->getGoogleProductCategory($lang),
        );
    }
}
