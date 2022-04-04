<?php

namespace App\Traits;

trait MetadataRules
{
    public function metadataRules(): array
    {
        return [
            'metadata' => ['array'],
            'metadata_private' => ['array'],
        ];
    }
}
