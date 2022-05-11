<?php

namespace App\Services\Contracts;

use App\Dtos\PackageTemplateDto;
use App\Models\PackageTemplate;

interface PackageTemplateServiceContract
{
    public function store(PackageTemplateDto $dto): PackageTemplate;

    public function update(PackageTemplate $packageTemplate, PackageTemplateDto $dto): PackageTemplate;

    public function destroy(PackageTemplate $packageTemplate): void;
}
