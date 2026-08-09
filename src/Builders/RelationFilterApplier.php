<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter\Builders;

use Illuminate\Database\Eloquent\Builder;
use Pradeepdev\SmartFilter\Contracts\OperatorRegistryContract;
use Pradeepdev\SmartFilter\DTOs\FilterInput;
use Pradeepdev\SmartFilter\DTOs\RelationFilterInput;
use Pradeepdev\SmartFilter\Support\FieldGuard;

/**
 * Applies relation filter inputs to an Eloquent query builder.
 *
 * Handles:
 *   - whereHas  (default — filters that must match)
 *   - orWhereHas
 *   - has()     (existence check: ?posts=has)
 *   - doesntHave() (non-existence check: ?posts=doesntHave)
 *   - orHas()
 *   - Nested relations via recursion: company.address.city
 *
 * The leaf field's value is applied by the correct operator from the registry,
 * exactly the same way flat filters work in FilterBuilder.
 */
final class RelationFilterApplier
{
    public function __construct(
        private readonly OperatorRegistryContract $registry,
    ) {}

    /**
     * Apply a single RelationFilterInput to the builder.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $builder
     * @return Builder<TModel>
     */
    public function apply(Builder $builder, RelationFilterInput $input): Builder
    {
        return match ($input->operator) {
            'has' => $builder->has($input->rootRelation()),
            'doesnt_have' => $builder->doesntHave($input->rootRelation()),
            'or_has' => $builder->orHas($input->rootRelation()),
            default => $this->applyWhereHas($builder, $input),
        };
    }

    // -------------------------------------------------------------------------

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $builder
     * @return Builder<TModel>
     */
    private function applyWhereHas(Builder $builder, RelationFilterInput $input): Builder
    {
        $rootRelation = $input->rootRelation();
        $nested = $input->withoutRootRelation();

        return $builder->whereHas($rootRelation, function (Builder $subQuery) use ($nested): void {
            if (count($nested->relation) > 0) {
                // Still more relations to traverse — recurse
                $this->applyWhereHas($subQuery, $nested);

                return;
            }

            // We're at the leaf: apply the operator to the field
            $this->applyLeafFilter($subQuery, $nested);
        });
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $builder
     */
    private function applyLeafFilter(Builder $builder, RelationFilterInput $input): void
    {
        if ($input->field === null) {
            return;
        }

        // Use a permissive guard for relation leaf fields — the field belongs to
        // the related model's table, not the parent's. The parent's $filterable
        // whitelist must not block valid fields on the related model.
        $permissiveGuard = new FieldGuard;
        $flatInput = new FilterInput($input->field, $input->operator, $input->value);
        $resolved = $permissiveGuard->resolveFilter($flatInput);

        if ($resolved === null) {
            return;
        }

        $operator = $this->registry->resolve($resolved->operator);
        $operator->apply($builder, $resolved);
    }
}
