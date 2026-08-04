<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter\Operators;

use Illuminate\Database\Eloquent\Builder;
use Pradeepdev\SmartFilter\Contracts\OperatorContract;
use Pradeepdev\SmartFilter\DTOs\FilterInput;
use Pradeepdev\SmartFilter\Enums\Operator;

final class LikeOperator implements OperatorContract
{
    public function apply(Builder $builder, FilterInput $input): Builder
    {
        // Wrap value in % wildcards. The value is already sanitised by the parser.
        return $builder->where($input->field, 'LIKE', '%' . $input->value . '%');
    }

    public function handles(): array
    {
        return [Operator::Like->value];
    }
}
