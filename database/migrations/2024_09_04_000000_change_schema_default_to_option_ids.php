<?php

declare(strict_types=1);

use Domain\ProductSchema\Models\Schema as ProductSchema;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        ProductSchema::query()->with('options')->chunkById(100, function (Collection $schemas) {
            /** @var ProductSchema $schema */
            foreach ($schemas as $schema) {
                $default = is_numeric($schema->default) ? intval($schema->default) : null;

                if ($default === null) {
                    continue;
                }

                $default_option = $schema->options->get($default, $schema->options->last());
                $schema->default = $default_option?->getKey();
                $schema->saveQuietly();
            }
        });
    }

    public function down(): void {}
};
