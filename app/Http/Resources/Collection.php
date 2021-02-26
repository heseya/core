<?php

namespace App\Http\Resources;

use Countable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\CollectsResources;
use Illuminate\Http\Resources\Json\PaginatedResourceResponse;
use Illuminate\Pagination\AbstractPaginator;
use IteratorAggregate;

class Collection extends Resource implements Countable, IteratorAggregate
{
    use CollectsResources;

    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects;

    /**
     * The mapped collection instance.
     *
     * @var \Illuminate\Support\Collection
     */
    public $collection;

    private bool $full;

    /**
     * Create a new resource instance.
     */
    public function __construct($resource, $collects, bool $full = false)
    {
        $this->collects = $collects;
        $this->full = $full;

        parent::__construct($resource);

        $this->resource = $this->collectResource($resource);
    }

    /**
     * Return the count of items in the resource collection.
     */
    public function count(): int
    {
        return $this->collection->count();
    }

    /**
     * Transform the resource into a JSON array.
     *
     * @param Request $request
     * @param bool $index
     *
     * @return array
     */
    public function toArray($request, bool $index = false): array
    {
        return $this->collection->map->toArray($request, !$this->full)->all();
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function toResponse($request): JsonResponse
    {
        if ($this->resource instanceof AbstractPaginator) {
            return $this->preparePaginatedResponse($request);
        }

        return parent::toResponse($request);
    }

    /**
     * Create a paginate-aware HTTP response.
     *
     * @param Request $request
     * @return JsonResponse
     */
    protected function preparePaginatedResponse($request): JsonResponse
    {
        // preserve All Query Parameters
        $this->resource->appends($request->query());

        return (new PaginatedResourceResponse($this))->toResponse($request);
    }
}
