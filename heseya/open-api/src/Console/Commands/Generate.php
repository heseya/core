<?php

namespace Heseya\OpenApi\Console\Commands;

use App\Models\App;
use App\Models\Interfaces\Translatable;
use App\Rules\Translations;
use App\Services\Contracts\AppServiceContract;
use Exception;
use Illuminate\Console\Command;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Optional;
use UnitEnum;

class Generate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'openapi:generate {path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Open API descriptions';

    /**
     * Execute the console command.
     *
     * @throws ReflectionException
     */
    public function handle(): void
    {
        $properties = [];
        $requiredProps = [];
        $path = $this->argument('path');
        $reflect = new ReflectionClass($path);

        $this->info("Generation request description for {$reflect->getShortName()}");

        foreach ($reflect->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            [$type, $additional] = $this->resolveType($prop->getType(), $prop);

            $properties[$prop->getName()] = [
                'type' => $type,
                ...$additional,
            ];

            if ($this->resolveRequired($prop->getType())) {
                $requiredProps[] = $prop->getName();
            }
        }

        $additional = [];

        if (count($requiredProps) > 0) {
            $additional = ['required' => $requiredProps];
        }

        $file = [$reflect->getShortName() => [
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => $properties,
                        ...$additional,
                    ],
                ],
            ],
        ]];

        file_put_contents(
            "./docs/requests/{$reflect->getShortName()}.json",
            json_encode($file),
        );

        $this->info('Done');
    }

    private function resolveType($type, ReflectionProperty $prop): array
    {
        if ($type instanceof ReflectionUnionType) {
            return $this->resolveUnionType($type->getTypes(), $prop);
        } elseif ($type instanceof ReflectionNamedType) {
            return $this->resolveNamedType($type, $prop);
        }


        throw new Exception("Unsupported reflection type for {$type->getName()}");
    }

    private function resolveUnionType(array $types, ReflectionProperty $prop): array
    {
        foreach ($types as $type) {
            try {
                return $this->resolveType($type, $prop);
            } catch (Exception) {
                continue;
            }
        }

        throw new Exception('Unsupported reflection types');
    }

    private function resolveNamedType(ReflectionNamedType $type, ReflectionProperty $prop): array
    {
        if ($type->isBuiltin()) {
            if ($type->getName() === 'array') {
                try {
                    return $this->resolveTranslations($prop);
                } catch (Exception) {}
            }
            return [$type->getName(), []];
        } elseif (enum_exists($type)) {
            return ['string', ['enum' => array_map(fn ($el) => $el->value, $type->getName()::cases())]];
        }

        throw new Exception("Unsupported reflection type for {$type->getName()}");
    }

    private function resolveRequired($types): bool
    {
        if ($types instanceof ReflectionNamedType) {
            return true;
        }

        foreach ($types->getTypes() as $type) {
            if ($type instanceof ReflectionNamedType && $type->getName() === Optional::class) {
                return false;
            }
        }

        return true;
    }

    private function resolveTranslations(ReflectionProperty $prop): array
    {
        $attributes = $prop->getAttributes(Rule::class);
        $properties = [];

        foreach ($attributes as $attribute) {
            foreach ($attribute->getArguments() as $argument) {
                if ($argument instanceof Translations) {
                    foreach ($argument->fields as $field) {
                        $properties[$field] = [
                            'type' => 'string',
                        ];
                    }
                }
            }
        }

        return ['object', [
            'properties' => [
                'lang_uuid' => [
                    'type' => 'object',
                    'properties' => $properties,
                ],
            ],
        ]];
    }
}
