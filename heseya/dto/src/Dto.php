<?php

namespace Heseya\Dto;

use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionProperty;
use TypeError;

class Dto
{
    /**
     * @throws DtoException
     */
    public function __construct(...$data)
    {
        $data = Collection::make($data);

        $reflection = new ReflectionClass($this);
        $properties = Collection::make($reflection->getProperties());

        $properties->each(function (ReflectionProperty $property) use ($data) {
            $property->setAccessible(true);
            $name = $property->getName();

            if (!$data->has($name)) {
                try {
                    return $property->setValue($this, new Missing());
                } catch (TypeError) {
                    $fullName = $this::class . '::$' . $name;
                    throw new DtoException("Property $fullName is required");
                }
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
