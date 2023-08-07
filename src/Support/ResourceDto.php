<?php

declare(strict_types=1);

namespace Support;

use App\Http\Resources\LanguageResource;
use App\Http\Resources\SeoMetadataResource;
use App\Services\Contracts\SeoMetadataServiceContract;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Spatie\LaravelData\Data;

class ResourceDto extends Data
{
    public function __construct(
        public array $data,
    ) {}

    public function with(): array
    {
        /** @var SeoMetadataServiceContract $seoMetadataService */
        $seoMetadataService = App::make(SeoMetadataServiceContract::class);

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
