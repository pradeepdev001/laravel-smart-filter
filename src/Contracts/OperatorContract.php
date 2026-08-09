<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Pradeepdev\SmartFilter\DTOs\FilterInput;

/**
 * Contract for query operator implementations.
 *
 * An Operator knows how to transform a FilterInput (field + value)
 * into an Eloquent query condition. Each operator handles exactly
 * one type of query comparison (equals, like, in, between, etc.).
 */
interface OperatorContract
{
    /**
     * Apply this operator's query logic to the builder.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $builder
     * @return Builder<TModel>
     */
    public function apply(Builder $builder, FilterInput $input): Builder;

    /**
     * Return the canonical string name(s) this operator handles.
     *
     * @return list<string>
     */
    public function handles(): array;
}
