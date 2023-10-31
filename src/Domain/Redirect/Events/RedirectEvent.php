<?php

declare(strict_types=1);

namespace Domain\Redirect\Events;

use App\Events\WebHookEvent;
use App\Http\Resources\RedirectResource;
use Domain\Redirect\Models\Redirect;

abstract class RedirectEvent extends WebHookEvent
{
    protected Redirect $redirect;

    public function __construct(Redirect $redirect)
    {
        parent::__construct();
        $this->redirect = $redirect;
    }

    /**
     * @return array<string,mixed>
     */
    public function getDataContent(): array
    {
        return RedirectResource::make($this->redirect)->resolve();
    }

    public function getDataType(): string
    {
        return Redirect::class;
    }
}
