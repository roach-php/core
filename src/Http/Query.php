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

namespace RoachPHP\Http;

final class Query
{
    /**
     * @param array<string, mixed> $values
     */
    private function __construct(private array $values)
    {
    }

    /**
     * @param array<string, mixed> $values
     */
    public static function fromArray(array $values): self
    {
        \ksort($values);

        return new self($values);
    }

    /**
     * Create a new Query instance by parsing the provided query string.
     */
    public static function parse(string $query): self
    {
        \parse_str($query, $values);

        // @phpstan-ignore argument.type
        return self::fromArray($values);
    }

    /**
     * Check if the query contains a given key.
     */
    public function has(string $key): bool
    {
        return \array_key_exists($key, $this->values);
    }

    /**
     * Returns the value of the query parameter with the provided key.
     *
     * @throws UnknownQueryParameterException thrown if no query parameter exists for the given key
     */
    public function get(string $key): mixed
    {
        if (!$this->has($key)) {
            throw UnknownQueryParameterException::forParameter($key);
        }

        return $this->values[$key];
    }

    /**
     * Returns the value of the query parameter with the provided key or a
     * default value if the key does not exist.
     */
    public function tryGet(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    /**
     * Returns the value of the query parameter with the provided key cast to
     * an integer.
     *
     * @throws QueryParameterTypeMismatchException thrown if the value is non-numeric
     * @throws UnknownQueryParameterException      thrown if no parameter exists for the given key
     */
    public function getInt(string $key): int
    {
        $value = $this->get($key);

        if (!\is_numeric($value)) {
            throw QueryParameterTypeMismatchException::forInt($key);
        }

        return (int) $value;
    }

    /**
     * Returns the value of the query parameter with the provided key cast to
     * an integer.
     *
     * @throws QueryParameterTypeMismatchException thrown if the value is non-numeric
     * @throws UnknownQueryParameterException      thrown if no parameter exists for the given key
     */
    public function getFloat(string $key): float
    {
        $value = $this->get($key);

        if (!\is_numeric($value)) {
            throw QueryParameterTypeMismatchException::forFloat($key);
        }

        return (float) $value;
    }

    /**
     * Returns the value of the query parameter with the provided key. Throws
     * an exception if the parameter value is not an array.
     *
     * @psalm-suppress MixedReturnTypeCoercion
     *
     * @throws QueryParameterTypeMismatchException thrown if the value is not an array
     * @throws UnknownQueryParameterException      thrown if no parameter exists for the given key
     *
     * @return array<string, mixed>
     */
    public function getArray(string $key): array
    {
        $value = $this->get($key);

        if (!\is_array($value)) {
            throw QueryParameterTypeMismatchException::forArray($key);
        }

        // @phpstan-ignore return.type
        return $value;
    }

    /**
     * Returns true if the query does not contain any keys. Returns false
     * otherwise.
     *
     * Note that if the query contains keys with "empty" or null values, the
     * query will NOT be considered empty.
     */
    public function isEmpty(): bool
    {
        return \count($this->values) === 0;
    }

    /**
     * Checks if this query is equal to the given query.
     *
     * Two queries are considered equal if all their parameters are equal. If
     * the query is provided as a string, it will be converted to a Query
     * object internally first.
     */
    public function equals(self|string $other): bool
    {
        if (\is_string($other)) {
            $other = self::parse($other);
        }

        return $this->values === $other->values;
    }

    public function toString(): string
    {
        return \http_build_query($this->values);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->values;
    }
}
