<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter\Operators;

use Illuminate\Database\Eloquent\Builder;
use Pradeepdev\SmartFilter\Contracts\OperatorContract;
use Pradeepdev\SmartFilter\DTOs\FilterInput;
use Pradeepdev\SmartFilter\Enums\Operator;
use Pradeepdev\SmartFilter\Exceptions\InvalidFilterValueException;

final class BooleanOperator implements OperatorContract
{
    private const TRUTHY = ['true', '1'];
    private const FALSY  = ['false', '0'];

    public function apply(Builder $builder, FilterInput $input): Builder
    {
        $raw = strtolower((string) $input->value);

        if (in_array($raw, self::TRUTHY, true)) {
            return $builder->where($input->field, true);
        }

        if (in_array($raw, self::FALSY, true)) {
            return $builder->where($input->field, false);
        }

        throw InvalidFilterValueException::invalidBooleanValue($input->field, (string) $input->value);
    }

    public function handles(): array
    {
        return [Operator::Boolean->value];
    }
}
