<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter\Contracts;

use Illuminate\Database\Eloquent\Builder;

/**
 * Contract for all filter implementations.
 *
 * Every filter is a self-contained unit that receives a query builder
 * and returns a (potentially modified) query builder. This keeps
 * each filter composable, testable, and side-effect-free on the input.
 */
interface FilterContract
{
    /**
     * Apply the filter to the given Eloquent query builder.
     */
    public function apply(Builder $builder): Builder;
}
