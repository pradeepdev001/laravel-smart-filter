<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter\Operators;

use Illuminate\Database\Eloquent\Builder;
use Pradeepdev\SmartFilter\Contracts\OperatorContract;
use Pradeepdev\SmartFilter\DTOs\FilterInput;
use Pradeepdev\SmartFilter\Enums\Operator;

final class LessThanOperator implements OperatorContract
{
    public function apply(Builder $builder, FilterInput $input): Builder
    {
        return $builder->where($input->field, '<', $input->value);
    }

    public function handles(): array
    {
        return [Operator::LessThan->value];
    }
}
