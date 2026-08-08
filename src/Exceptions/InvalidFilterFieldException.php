<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter\Exceptions;

use InvalidArgumentException;

final class InvalidFilterFieldException extends InvalidArgumentException
{
    /**
     * @param  list<string>  $allowed
     */
    public static function fieldNotAllowed(string $field, array $allowed): self
    {
        $list = implode(', ', $allowed);

        return new self(
            "SmartFilter: Field \"{$field}\" is not in the allowed fields list. Allowed: {$list}."
        );
    }

    public static function fieldIgnored(string $field): self
    {
        return new self(
            "SmartFilter: Field \"{$field}\" is in the ignored fields list and cannot be filtered."
        );
    }
}
