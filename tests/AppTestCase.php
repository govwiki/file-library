<?php

namespace Tests;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class AppTestCase
 *
 * @package Tests
 */
class AppTestCase extends TestCase
{

    /**
     * @param string $interface Required interface fqcn.
     *
     * @return MockObject
     */
    protected function createMockForInterface(string $interface): MockObject
    {
        $methods = [];

        $reflection = new \ReflectionClass($interface);
        foreach ($reflection->getMethods() as $method) {
            $methods[] = $method->getName();
        }

        return $this->getMockBuilder($interface)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * @param object $object   Changed object.
     * @param string $property Changed object property name.
     * @param mixed  $value    New property value.
     *
     * @return void
     */
    protected function setProperty($object, string $property, $value)
    {
        $setter = function () use ($property, $value) {
            $this->{$property} = $value;
        };

        $setter->call($object, $object);
    }
}
