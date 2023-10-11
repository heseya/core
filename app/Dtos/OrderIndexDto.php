<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\OrderIndexRequest;
use Heseya\Dto\Dto;
use Illuminate\Foundation\Http\FormRequest;

class OrderIndexDto extends Dto implements InstantiateFromRequest
{
    private string|null $search;
    private string|null $sort;
    private string|null $status_id;
    private string|null $shipping_method_id;

    public static function instantiateFromRequest(FormRequest|OrderIndexRequest $request): self
    {
        return new self(
            search: $request->input('search'),
            sort: $request->input('sort', 'created_at:desc'),
            status_id: $request->input('status_id'),
            shipping_method_id: $request->input('shipping_method_id'),
        );
    }

    public function getSearch(): string|null
    {
        return $this->search;
    }

    public function getSort(): string|null
    {
        return $this->sort;
    }

    public function getStatusId(): string|null
    {
        return $this->status_id;
    }

    public function getShippingMethodId(): string|null
    {
        return $this->shipping_method_id;
    }

    public function getSearchCriteria(): array
    {
        $data = [];

        if ($this->getSearch() !== null) {
            $data['search'] = $this->getSearch();
        }
        if ($this->getStatusId() !== null) {
            $data['status_id'] = $this->getStatusId();
        }
        if ($this->getShippingMethodId() !== null) {
            $data['shipping_method_id'] = $this->getShippingMethodId();
        }

        return $data;
    }
}
