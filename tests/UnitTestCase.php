<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase;
use ReflectionClass;

class UnitTestCase extends TestCase
{
    use CreatesApplication;

    protected function callMethod(object $object, string $method, array $parameters = []): mixed
    {
        $className = $object::class;
        $reflection = new ReflectionClass($className);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
