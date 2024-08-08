<?php

namespace App\Events;

use App\Http\Resources\OptionResource;
use App\Models\Option;

abstract class OptionEvent extends WebHookEvent
{
    protected Option $option;

    public function __construct(Option $option)
    {
        parent::__construct();
        $this->option = $option;
    }

    public function getDataContent(): array
    {
        return OptionResource::make($this->option)->resolve();
    }

    public function getDataType(): string
    {
        return $this->getModelClass($this->option);
    }

    public function getOption(): Option
    {
        return $this->option;
    }
}
