<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter\Operators;

use Illuminate\Database\Eloquent\Builder;
use Pradeepdev\SmartFilter\Contracts\OperatorContract;
use Pradeepdev\SmartFilter\DTOs\FilterInput;
use Pradeepdev\SmartFilter\Enums\Operator;

final class IsNullOperator implements OperatorContract
{
    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     * @param  Builder<TModel>  $builder
     * @return Builder<TModel>
     */
    public function apply(Builder $builder, FilterInput $input): Builder
    {
        return $builder->whereNull($input->field);
    }

    public function handles(): array
    {
        return [Operator::IsNull->value];
    }
}
