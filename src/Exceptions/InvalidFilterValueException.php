<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter\Exceptions;

use InvalidArgumentException;

final class InvalidFilterValueException extends InvalidArgumentException
{
    public static function betweenRequiresTwoValues(string $field): self
    {
        return new self(
            "SmartFilter: The \"between\" operator on field \"{$field}\" requires exactly two comma-separated values, e.g. between(100,500)."
        );
    }

    public static function inRequiresAtLeastOneValue(string $field): self
    {
        return new self(
            "SmartFilter: The \"in\" operator on field \"{$field}\" requires at least one value, e.g. in(a,b,c)."
        );
    }

    public static function invalidBooleanValue(string $field, string $value): self
    {
        return new self(
            "SmartFilter: Field \"{$field}\" expects a boolean value. Received: \"{$value}\". Use 1, 0, true, or false."
        );
    }
}
