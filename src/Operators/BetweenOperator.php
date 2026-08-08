<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter\Operators;

use Illuminate\Database\Eloquent\Builder;
use Pradeepdev\SmartFilter\Contracts\OperatorContract;
use Pradeepdev\SmartFilter\DTOs\FilterInput;
use Pradeepdev\SmartFilter\Enums\Operator;
use Pradeepdev\SmartFilter\Exceptions\InvalidFilterValueException;

final class BetweenOperator implements OperatorContract
{
    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     * @param  Builder<TModel>  $builder
     * @return Builder<TModel>
     */
    public function apply(Builder $builder, FilterInput $input): Builder
    {
        $values = is_array($input->value) ? $input->value : [$input->value];

        if (count($values) !== 2) {
            throw InvalidFilterValueException::betweenRequiresTwoValues($input->field);
        }

        return $builder->whereBetween($input->field, [$values[0], $values[1]]);
    }

    public function handles(): array
    {
        return [Operator::Between->value];
    }
}
