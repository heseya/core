<?php

namespace App\Events;

use App\Http\Resources\LanguageResource;
use App\Models\Language;

abstract class LanguageEvent extends WebHookEvent
{
    protected Language $language;

    public function __construct(Language $language)
    {
        parent::__construct();
        $this->language = $language;
    }

    public function isHidden(): bool
    {
        return !$this->language->hidden;
    }

    public function getDataContent(): array
    {
        return LanguageResource::make($this->language)->resolve();
    }

    public function getDataType(): string
    {
        return $this->getModelClass($this->language);
    }
}
