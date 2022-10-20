<?php

namespace App\Services;

use App\Dtos\PackageTemplateDto;
use App\Models\PackageTemplate;
use App\Services\Contracts\MetadataServiceContract;
use App\Services\Contracts\PackageTemplateServiceContract;
use Heseya\Dto\Missing;

class PackageTemplateService implements PackageTemplateServiceContract
{
    public function __construct(
        private MetadataServiceContract $metadataService
    ) {
    }

    public function store(PackageTemplateDto $dto): PackageTemplate
    {
        $package = PackageTemplate::create($dto->toArray());

        if (!($dto->getMetadata() instanceof Missing)) {
            $this->metadataService->sync($package, $dto->getMetadata());
        }

        return $package;
    }

    public function update(PackageTemplate $packageTemplate, PackageTemplateDto $dto): PackageTemplate
    {
        $packageTemplate->update($dto->toArray());

        return $packageTemplate;
    }

    public function destroy(PackageTemplate $packageTemplate): void
    {
        $packageTemplate->delete();
    }
}
