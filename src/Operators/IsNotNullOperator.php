<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter\Operators;

use Illuminate\Database\Eloquent\Builder;
use Pradeepdev\SmartFilter\Contracts\OperatorContract;
use Pradeepdev\SmartFilter\DTOs\FilterInput;
use Pradeepdev\SmartFilter\Enums\Operator;

final class IsNotNullOperator implements OperatorContract
{
    public function apply(Builder $builder, FilterInput $input): Builder
    {
        return $builder->whereNotNull($input->field);
    }

    public function handles(): array
    {
        return [Operator::IsNotNull->value];
    }
}
