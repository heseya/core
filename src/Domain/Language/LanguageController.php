<?php

declare(strict_types=1);

namespace Domain\Language;

use App\Exceptions\StoreException;
use App\Http\Controllers\Controller;
use App\Http\Resources\LanguageResource;
use Domain\Language\Dtos\LanguageCreateDto;
use Domain\Language\Dtos\LanguageUpdateDto;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

final class LanguageController extends Controller
{
    public function __construct(
        private readonly LanguageService $languageService,
    ) {}

    public function index(): JsonResource
    {
        $query = Language::query();

        if (Gate::denies('languages.show_hidden')) {
            $query->where('hidden', false);
        }

        return LanguageResource::collection(
            $query->paginate(Config::get('pagination.per_page')),
        );
    }

    public function store(LanguageCreateDto $dto): JsonResource
    {
        return LanguageResource::make(
            $this->languageService->create($dto),
        );
    }

    /**
     * @throws StoreException
     */
    public function update(Language $language, LanguageUpdateDto $dto): JsonResource
    {
        return LanguageResource::make(
            $this->languageService->update($language, $dto),
        );
    }

    /**
     * @throws StoreException
     */
    public function destroy(Language $language): HttpResponse
    {
        $this->languageService->delete($language);

        return Response::noContent();
    }
}
