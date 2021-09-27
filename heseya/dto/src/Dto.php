<?php

namespace Heseya\Dto;

use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

class Dto
{
    public function __construct(...$data)
    {
        $data = Collection::make($data);

        $reflection = new ReflectionClass($this);
        $properties = Collection::make($reflection->getProperties());

        $properties->each(function (ReflectionProperty $property) use ($data) {
            $property->setAccessible(true);
            $name = $property->getName();

            $types = Collection::make(
                $property->getType() instanceof ReflectionNamedType ? [$property->getType()]
                    : $property->getType()->getTypes(),
            );

            $optional = $types->contains(fn ($type) => $type->getName() === Missing::class);
            $allowsNull = $property->getType()->allowsNull();

            if (!$data->has($name)) {
                if (!$optional) {
                    throw new DtoException("Property ${name} is required");
                }

                return $property->setValue($this, new Missing());
            }

            if ($data->get($name) === null && !$allowsNull) {
                throw new DtoException("${name} property cannot be null");
            }

            $property->setValue($this, $data->get($name));
        });
    }

    public function toArray(): array
    {
        $reflection = new ReflectionClass($this);
        $properties = Collection::make($reflection->getProperties());

        return $properties->reduce(function ($data, ReflectionProperty $property) {
            $property->setAccessible(true);
            $name = $property->getName();
            $value = $property->getValue($this);

            if ($value instanceof Missing) {
                return $data;
            }

            $hidden = Collection::make($property->getAttributes())
                ->contains(fn ($attribute) => $attribute->getName() === Hidden::class);

            if ($hidden) {
                return $data;
            }

            return $data + [$name => $value];
        }, []);
    }
}
