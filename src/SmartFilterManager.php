<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter;

use Pradeepdev\SmartFilter\Contracts\OperatorContract;
use Pradeepdev\SmartFilter\Contracts\OperatorRegistryContract;

/**
 * Developer-facing API for SmartFilter.
 *
 * Resolved via the SmartFilter facade. Provides extension points
 * without coupling callers to internal registry details.
 */
final class SmartFilterManager
{
    public function __construct(
        private readonly OperatorRegistryContract $registry,
    ) {}

    /**
     * Register a custom operator.
     *
     * The operator's handles() method determines the operator name(s) it claims.
     * You can also pass a callable that returns an OperatorContract.
     *
     * @param  OperatorContract|callable(): OperatorContract  $operator
     */
    public function extend(OperatorContract|callable $operator): void
    {
        $this->registry->register($operator);
    }

    /**
     * Expose the registry for advanced use cases.
     */
    public function registry(): OperatorRegistryContract
    {
        return $this->registry;
    }
}
