<?php

declare(strict_types=1);

namespace Domain\GoogleCategory\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use Domain\GoogleCategory\Enums\GoogleCategoriesLang;
use Domain\GoogleCategory\Services\Contracts\GoogleCategoryServiceContract;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;

final class GoogleCategoryController extends Controller
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
