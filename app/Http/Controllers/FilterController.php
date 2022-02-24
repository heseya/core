<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterIndexRequest;
use App\Http\Resources\AttributeResource;
use App\Models\Attribute;
use Illuminate\Http\Resources\Json\JsonResource;

class FilterController extends Controller
{
    public function indexBySetsIds(FilterIndexRequest $request): JsonResource
    {
        if (!$request->has('sets')) {
            return AttributeResource::collection(Attribute::where('global', 1)->get());
        }

        return AttributeResource::collection(
            Attribute::whereHas(
                'productSets',
                fn ($query) => $query->whereIn('product_set_id', $request->sets)
            )->orWhere('global', 1)->with('options')->get()
        );
    }
}
