<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter\Builders;

use Illuminate\Database\Eloquent\Builder;
use Pradeepdev\SmartFilter\Collections\FilterCollection;
use Pradeepdev\SmartFilter\Contracts\OperatorRegistryContract;
use Pradeepdev\SmartFilter\Support\FieldGuard;

/**
 * Applies a FilterCollection to an Eloquent Builder.
 *
 * The FilterBuilder is the last stop in the pipeline. It:
 *   1. Validates and resolves fields via FieldGuard
 *   2. Delegates each filter to the correct operator
 *   3. Applies sorting
 *   4. Applies search (if searchable fields are configured)
 *
 * This class knows nothing about HTTP. It only works with DTOs.
 */
final class FilterBuilder
{
    public function __construct(
        private readonly OperatorRegistryContract $registry,
        private readonly FieldGuard $guard,
    ) {}

    /**
     * Apply all filters, sorts, and search from the collection to the builder.
     */
    public function apply(Builder $builder, FilterCollection $collection): Builder
    {
        $builder = $this->applyFilters($builder, $collection);
        $builder = $this->applySorts($builder, $collection);
        $builder = $this->applySearch($builder, $collection);

        return $builder;
    }

    // -------------------------------------------------------------------------

    private function applyFilters(Builder $builder, FilterCollection $collection): Builder
    {
        foreach ($collection->filters() as $filter) {
            $resolved = $this->guard->resolveFilter($filter);

            if ($resolved === null) {
                continue; // Silently skipped (field not allowed or ignored)
            }

            $operator = $this->registry->resolve($resolved->operator);
            $builder  = $operator->apply($builder, $resolved);
        }

        return $builder;
    }

    private function applySorts(Builder $builder, FilterCollection $collection): Builder
    {
        foreach ($collection->sorts() as $sort) {
            $resolved = $this->guard->resolveSort($sort);

            if ($resolved === null) {
                continue;
            }

            $builder->orderBy($resolved->field, $resolved->direction->value);
        }

        return $builder;
    }

    private function applySearch(Builder $builder, FilterCollection $collection): Builder
    {
        $search = $collection->search();

        if ($search === null || $search->fields === []) {
            return $builder;
        }

        // Wrap all search conditions in a single OR group so they don't
        // interfere with other WHERE conditions on the query.
        $builder->where(function (Builder $query) use ($search): void {
            foreach ($search->fields as $field) {
                $resolved = $this->guard->resolveFilter(
                    new \Pradeepdev\SmartFilter\DTOs\FilterInput($field, 'like', $search->term)
                );

                if ($resolved !== null) {
                    $query->orWhere($field, 'LIKE', '%' . $search->term . '%');
                }
            }
        });

        return $builder;
    }
}
