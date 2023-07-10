<?php

namespace App\Http\Controllers;

use App\Dtos\LanguageDto;
use App\Http\Requests\LanguageCreateRequest;
use App\Http\Requests\LanguageUpdateRequest;
use App\Http\Resources\LanguageResource;
use App\Models\Language;
use App\Services\Contracts\LanguageServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

class LanguageController extends Controller
{
    public function __construct(
        private LanguageServiceContract $languageService,
    ) {}

    public function index(): JsonResource
    {
        $query = Language::query();

        if (Gate::denies('languages.show_hidden')) {
            $query->where('hidden', false);
        }

        return LanguageResource::collection(
            $query->paginate(Config::get('pagination.per_page'))
        );
    }

    public function store(LanguageCreateRequest $request): JsonResource
    {
        $language = $this->languageService->create(
            LanguageDto::instantiateFromRequest($request),
        );

        return LanguageResource::make($language);
    }

    public function update(Language $language, LanguageUpdateRequest $request): JsonResource
    {
        $language = $this->languageService->update(
            $language,
            LanguageDto::instantiateFromRequest($request),
        );

        return LanguageResource::make($language);
    }

    public function destroy(Language $language): JsonResponse
    {
        $this->languageService->delete($language);

        return Response::json(null, 204);
    }
}
