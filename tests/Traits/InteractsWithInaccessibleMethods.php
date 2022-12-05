<?php

namespace Tests\Traits;

use ReflectionClass;

trait InteractsWithInaccessibleMethods
{
    public function invokeMethod(&$object, string $method, array $params = []): mixed
    {
        $reflectionClass = new ReflectionClass(get_class($object));
        $reflectionMethod = $reflectionClass->getMethod($method);
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invokeArgs($object, $params);
    }
}
