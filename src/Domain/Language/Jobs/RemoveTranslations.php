<?php

declare(strict_types=1);

namespace Domain\Language\Jobs;

use Domain\Language\Language;
use Domain\Language\LanguageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class RemoveTranslations implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected Language $language;

    public function __construct(Language $language)
    {
        $this->language = $language;
    }

    public function handle(LanguageService $languageService): void
    {
        $languageService->removeAllLanguageTranslations($this->language);
    }
}
