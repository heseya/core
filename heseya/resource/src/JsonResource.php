<?php

namespace Heseya\Resource;

use Illuminate\Http\Request;

class JsonResource extends \Illuminate\Http\Resources\Json\JsonResource
{
    protected bool $baseOnly = false;
    protected bool $isIndex = false;

    /**
     * Create a new anonymous resource collection.
     */
    public static function collection($resource)
    {
        return tap(new ResourceCollection($resource, static::class), function ($collection) {
            if (property_exists(static::class, 'preserveKeys')) {
                $collection->preserveKeys = (new static([]))->preserveKeys === true;
            }
        });
    }

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     *
     * @return array
     */
    public function toArray($request): array
    {
        if ($this->resource === null) {
            return [];
        }

        if (is_array($this->resource)) {
            $this->resource = (object) $this->resource;
        }

        if ($this->baseOnly) {
            return $this->base($request);
        }

        if ($this->isIndex) {
            return array_merge(
                $this->base($request),
                $this->index($request),
            );
        }

        return array_merge(
            $this->base($request),
            $this->view($request),
        );
    }

    /**
     * Parameters displayed on index and view
     */
    public function base(Request $request): array
    {
        return [];
    }

    /**
     * Parameters displayed on index only
     */
    public function index(Request $request): array
    {
        return [];
    }

    /**
     * Parameters displayed on view only
     */
    public function view(Request $request): array
    {
        return [];
    }

    /**
     * Display only base parameters
     */
    public function baseOnly(bool $baseOnly = true): self
    {
        $this->baseOnly = $baseOnly;

        return $this;
    }

    /**
     * Display only base parameters
     */
    public function isIndex(bool $isIndex = true): self
    {
        $this->isIndex = $isIndex;

        return $this;
    }
}
