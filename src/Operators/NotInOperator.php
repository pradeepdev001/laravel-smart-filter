<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter\Operators;

use Illuminate\Database\Eloquent\Builder;
use Pradeepdev\SmartFilter\Contracts\OperatorContract;
use Pradeepdev\SmartFilter\DTOs\FilterInput;
use Pradeepdev\SmartFilter\Enums\Operator;
use Pradeepdev\SmartFilter\Exceptions\InvalidFilterValueException;

final class NotInOperator implements OperatorContract
{
    public function apply(Builder $builder, FilterInput $input): Builder
    {
        $values = is_array($input->value) ? $input->value : [$input->value];

        if ($values === []) {
            throw InvalidFilterValueException::inRequiresAtLeastOneValue($input->field);
        }

        return $builder->whereNotIn($input->field, $values);
    }

    public function handles(): array
    {
        return [Operator::NotIn->value];
    }
}
