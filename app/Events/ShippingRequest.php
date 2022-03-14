<?php

namespace App\Events;

use App\Http\Resources\OrderResource;
use App\Http\Resources\PackageTemplateResource;
use App\Models\Order;
use App\Models\PackageTemplate;

abstract class ShippingRequest extends WebHookEvent
{
    protected Order $order;
    protected PackageTemplate $packageTemplate;

    public function __construct(Order $order, PackageTemplate $packageTemplate)
    {
        parent::__construct();
        $this->order = $order;
        $this->packageTemplate = $packageTemplate;
    }

    public function getDataContent(): array
    {
        $packageTemplate = PackageTemplateResource::make($this->packageTemplate)->resolve();
        $packageTemplate['package_template_id'] = $packageTemplate['id'];
        unset($packageTemplate['id']);

        return array_merge(
            OrderResource::make($this->order)->resolve(),
            $packageTemplate
        );
    }

    public function getDataType(): string
    {
        return 'ShippingRequest';
    }
}
