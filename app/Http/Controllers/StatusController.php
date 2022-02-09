<?php

namespace App\Http\Controllers;

use App\Exceptions\Error;
use App\Http\Requests\StatusCreateRequest;
use App\Http\Requests\StatusReorderRequest;
use App\Http\Requests\StatusUpdateRequest;
use App\Http\Resources\StatusResource;
use App\Models\Status;
use App\Services\Contracts\TranslationServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class StatusController extends Controller
{
    public function __construct(
        private TranslationServiceContract $translationService,
    ) {
    }

    public function index(): JsonResource
    {
        return StatusResource::collection(Status::orderBy('order')->get());
    }

    public function store(StatusCreateRequest $request): JsonResource
    {
        $status = Status::make($request->validated());

        foreach($request->input('translations') as $lang => $translations) {
            $status->setLocale($lang)->fill($translations);
        }

        $this->translationService->checkPublished($status, ['name']);

        $status->save();

        return StatusResource::make($status);
    }

    public function update(Status $status, StatusUpdateRequest $request): JsonResource
    {
        $status->fill($request->validated());

        foreach($request->input('translations', []) as $lang => $translations) {
            $status->setLocale($lang)->fill($translations);
        }

        $this->translationService->checkPublished($status, ['name']);

        $status->save();

        return StatusResource::make($status);
    }

    public function reorder(StatusReorderRequest $request): JsonResponse
    {
        foreach ($request->input('statuses') as $key => $id) {
            Status::where('id', $id)->update(['order' => $key]);
        }

        return response()->json(null, 204);
    }

    public function destroy(Status $status): JsonResponse
    {
        if (Status::count() <= 1) {
            return Error::abort(
                'Musi istnieć co najmniej jeden status.',
                409,
            );
        }

        if ($status->orders()->count() > 0) {
            return Error::abort(
                'Status nie może być usunięty, ponieważ jest przypisany do zamówienia.',
                409,
            );
        }

        $status->delete();

        return response()->json(null, 204);
    }
}
