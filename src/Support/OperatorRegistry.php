<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter\Support;

use Pradeepdev\SmartFilter\Contracts\OperatorContract;
use Pradeepdev\SmartFilter\Contracts\OperatorRegistryContract;
use Pradeepdev\SmartFilter\Exceptions\UnknownOperatorException;

/**
 * Central registry for all filter operators.
 *
 * Operators are keyed by the string names they declare in handles().
 * Custom operators registered via SmartFilter::extend() land here.
 *
 * This class is intentionally NOT final so the container can swap it
 * out in tests or for framework-specific registries.
 */
class OperatorRegistry implements OperatorRegistryContract
{
    /** @var array<string, OperatorContract> */
    private array $operators = [];

    public function register(OperatorContract|callable $operator): void
    {
        if (is_callable($operator)) {
            $operator = $operator();
        }

        foreach ($operator->handles() as $name) {
            $this->operators[$name] = $operator;
        }
    }

    public function resolve(string $name): OperatorContract
    {
        if (! isset($this->operators[$name])) {
            throw UnknownOperatorException::for($name, $this->names());
        }

        return $this->operators[$name];
    }

    public function has(string $name): bool
    {
        return isset($this->operators[$name]);
    }

    public function names(): array
    {
        return array_keys($this->operators);
    }
}
