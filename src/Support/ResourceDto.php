<?php

declare(strict_types=1);

namespace Support;

use App\Http\Resources\LanguageResource;
use Domain\Seo\Resources\SeoMetadataResource;
use Domain\Seo\SeoMetadataService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Spatie\LaravelData\Data;

final class ResourceDto extends Data
{
    /**
     * @phpstan-ignore-next-line
     */
    public function __construct(
        public array $data,
    ) {}

    /**
     * @return LanguageResource[][]|SeoMetadataResource[][]
     */
    public function with(): array
    {
        /** @var SeoMetadataService $seoMetadataService */
        $seoMetadataService = App::make(SeoMetadataService::class);

        return [
            'meta' => [
                'language' => LanguageResource::make(
                    Config::get('language.model'),
                ),
                'seo' => SeoMetadataResource::make($seoMetadataService->getGlobalSeo()),
            ],
        ];
    }
}
