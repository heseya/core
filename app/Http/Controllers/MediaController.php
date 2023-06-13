<?php

namespace App\Http\Controllers;

use App\Dtos\MediaDto;
use App\Http\Requests\MediaIndexRequest;
use App\Http\Requests\MediaStoreRequest;
use App\Http\Requests\MediaUpdateRequest;
use App\Http\Resources\MediaDetailResource;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use App\Services\Contracts\MediaServiceContract;
use Heseya\Dto\DtoException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Response;

class MediaController extends Controller
{
    private MediaServiceContract $mediaServiceContract;

    public function __construct(MediaServiceContract $mediaServiceContract)
    {
        $this->mediaServiceContract = $mediaServiceContract;
    }

    public function index(MediaIndexRequest $request): JsonResource
    {
        /** @var Builder $query */
        $query = Media::searchByCriteria($request->validated());

        return MediaDetailResource::collection($query->paginate(Config::get('pagination.per_page')));
    }

    /**
     * @throws DtoException
     */
    public function store(MediaStoreRequest $request): JsonResource
    {
        $media = $this->mediaServiceContract->store(MediaDto::instantiateFromRequest($request));

        return MediaDetailResource::make($media);
    }

    /**
     * @throws DtoException
     */
    public function update(Media $media, MediaUpdateRequest $request): JsonResource
    {
        $media = $this->mediaServiceContract->update(
            $media,
            MediaDto::instantiateFromRequest($request),
        );

        return MediaResource::make($media);
    }

    public function destroy(Media $media): JsonResponse
    {
        $this->mediaServiceContract->destroy($media);

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
