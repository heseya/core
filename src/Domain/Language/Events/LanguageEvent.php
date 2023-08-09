<?php

declare(strict_types=1);

namespace Domain\Language\Events;

use App\Events\WebHookEvent;
use App\Http\Resources\LanguageResource;
use Domain\Language\Language;

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

    /**
     * @return array<string, mixed>
     */
    public function getDataContent(): array
    {
        return LanguageResource::make($this->language)->resolve();
    }

    public function getDataType(): string
    {
        return $this->getModelClass($this->language);
    }
}
