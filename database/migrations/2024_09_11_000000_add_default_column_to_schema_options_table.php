<?php

declare(strict_types=1);

use App\Models\Option;
use Domain\ProductSchema\Models\Schema as ProductSchema;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Ramsey\Uuid\Uuid;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('options', function (Blueprint $table) {
            $table->boolean('default')->default(false);
        });

        ProductSchema::query()->with('options')->chunkById(100, function (Collection $schemas) {
            /** @var ProductSchema $schema */
            foreach ($schemas as $schema) {
                $default = match (true) {
                    is_numeric($schema->default) => intval($schema->default),
                    Uuid::isValid($schema->default) => $schema->default,
                    default => null,
                };

                if ($default === null && !$schema->required) {
                    continue;
                }

                $default_option = match (true) {
                    is_int($default) => $schema->options->get($default),
                    is_string($default) => $schema->options->first(fn(Option $option): bool => $option->getKey() === $default),
                } ?? $schema->options->first();

                if ($default_option === null) {
                    continue;
                }

                $default_option->default = true;
                $default_option->save();
            }
        });
    }

    public function down(): void
    {
        Schema::table('options', function (Blueprint $table) {
            $table->dropColumn('default');
        });
    }
};
