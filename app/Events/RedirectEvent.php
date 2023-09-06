<?php

namespace App\Events;

use App\Http\Resources\RedirectResource;
use App\Models\Redirect;

abstract class RedirectEvent extends WebHookEvent
{
    protected Redirect $redirect;

    public function __construct(Redirect $redirect)
    {
        parent::__construct();
        $this->redirect = $redirect;
    }

    public function getDataContent(): array
    {
        return RedirectResource::make($this->redirect)->resolve();
    }

    public function getDataType(): string
    {
        return Redirect::class;
    }
}
