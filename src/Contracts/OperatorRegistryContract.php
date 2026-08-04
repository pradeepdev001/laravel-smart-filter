<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter\Contracts;

/**
 * Contract for the operator registry.
 *
 * The registry is the single source of truth for all registered operators.
 * It maps operator name strings to concrete OperatorContract implementations,
 * and is the extension point for custom operators (Phase 5).
 */
interface OperatorRegistryContract
{
    /**
     * Register an operator instance or a factory closure.
     *
     * @param  OperatorContract|callable(): OperatorContract  $operator
     */
    public function register(OperatorContract|callable $operator): void;

    /**
     * Resolve an operator by its string name.
     *
     * @throws \Pradeepdev\SmartFilter\Exceptions\UnknownOperatorException
     */
    public function resolve(string $name): OperatorContract;

    /**
     * Determine whether a given operator name is registered.
     */
    public function has(string $name): bool;

    /**
     * Return all registered operator names.
     *
     * @return list<string>
     */
    public function names(): array;
}
