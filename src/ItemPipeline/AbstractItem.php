<?php

declare(strict_types=1);

/**
 * Copyright (c) 2024 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/roach-php/roach
 */

namespace RoachPHP\ItemPipeline;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use RoachPHP\Support\Droppable;
use RuntimeException;

abstract class AbstractItem implements ItemInterface
{
    use Droppable;

    final public function all(): array
    {
        $reflectionClass = new ReflectionClass($this);
        $properties = $reflectionClass->getProperties(ReflectionProperty::IS_PUBLIC);

        return \array_reduce(
            $properties,
            function (array $data, ReflectionProperty $property): array {
                /** @psalm-suppress MixedAssignment */
                $data[$property->getName()] = $property->getValue($this);

                return $data;
            },
            [],
        );
    }

    final public function get(string $key, mixed $default = null): mixed
    {
        $reflectionClass = new ReflectionClass($this);

        try {
            $property = $reflectionClass->getProperty($key);
        } catch (ReflectionException) {
            return $default;
        }

        if (!$property->isPublic()) {
            return $default;
        }

        return $property->getValue($this) ?: $default;
    }

    final public function set(string $key, mixed $value): ItemInterface
    {
        $reflectionClass = new ReflectionClass($this);

        try {
            $property = $reflectionClass->getProperty($key);
        } catch (ReflectionException) {
            throw new InvalidArgumentException(
                \sprintf('No public property %s exists on class %s', $key, static::class),
            );
        }

        if (!$property->isPublic()) {
            throw new InvalidArgumentException(
                \sprintf('No public property %s exists on class %s', $key, static::class),
            );
        }

        $property->setValue($this, $value);

        return $this;
    }

    final public function has(string $key): bool
    {
        $reflectionClass = new ReflectionClass($this);

        try {
            $property = $reflectionClass->getProperty($key);

            return $property->isPublic();
        } catch (ReflectionException) {
            return false;
        }
    }

    final public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    final public function offsetGet(mixed $offset): mixed
    {
        /** @psalm-suppress DocblockTypeContradiction */
        if (!\is_string($offset)) {
            throw new InvalidArgumentException('Offset needs to be a string');
        }

        /** @psalm-suppress MixedReturnStatement */
        return $this->get($offset);
    }

    final public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!\is_string($offset)) {
            throw new InvalidArgumentException('Offset needs to be a string');
        }

        $this->set($offset, $value);
    }

    final public function offsetUnset(mixed $offset): void
    {
        throw new RuntimeException('Unsetting properties is not supported for custom item classes');
    }
}
