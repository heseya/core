<?php

namespace App\Traits;

use Illuminate\Support\Facades\Gate;

trait MetadataResourceTrait
{
    public function metadata_resource(?string $prefix = null): array
    {
        $data['metadata'] = [];
        foreach ($this->metadata as $metadata) {
            $data['metadata'][$metadata->name] = $metadata->value;
        }

        if ($prefix !== null && Gate::allows("{$prefix}.show_metadata_private")) {
            $data['metadata_private'] = [];
            foreach ($this->metadata_private as $metadata_private) {
                $data['metadata_private'][$metadata_private->name] = $metadata_private->value;
            }
        }

        return $data;
    }
}
