<?php

namespace App\Http\Controllers;

use App\DTO\Language\LanguageCreateDto;
use App\DTO\Language\LanguageUpdateDto;
use App\Http\Resources\LanguageResource;
use App\Models\Language;
use App\Services\Contracts\LanguageServiceContract;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

final class LanguageController extends Controller
{
    public function __construct(
        private readonly LanguageServiceContract $languageService,
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

    public function store(LanguageCreateDto $dto): JsonResource
    {
        return LanguageResource::make(
            $this->languageService->create($dto),
        );
    }

    public function update(Language $language, LanguageUpdateDto $dto): JsonResource
    {
        return LanguageResource::make(
            $this->languageService->update($language, $dto),
        );
    }

    public function destroy(Language $language): HttpResponse
    {
        $this->languageService->delete($language);

        return Response::noContent();
    }
}
