<?php

namespace App\Http\Controllers;

use App\Exceptions\StoreException;
use App\Http\Requests\LanguageCreateRequest;
use App\Http\Requests\LanguageUpdateRequest;
use App\Http\Resources\LanguageResource;
use App\Models\Language;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

class LanguageController extends Controller
{
    public function index(): JsonResource
    {
        $query = Language::query();

        if (Gate::allows('products.show_hidden')) {
            $query->where('hidden', false);
        }

        return LanguageResource::collection(
            $query->paginate(Config::get('pagination.per_page'))
        );
    }

    public function store(LanguageCreateRequest $request): JsonResource
    {
        $language = Language::create($request->validated());

        return LanguageResource::make($language);
    }

    public function update(Language $language, LanguageUpdateRequest $request): JsonResource
    {
        $language->update($request->validated());

        return LanguageResource::make($language);
    }

    public function destroy(Language $language): JsonResponse
    {
        if (Language::count() <= 1) {
            throw new StoreException('There must be at least one language.');
        }

        $language->delete();

        return Response::json(null, 204);
    }
}
