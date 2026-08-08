<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter\Exceptions;

use InvalidArgumentException;

final class UnknownOperatorException extends InvalidArgumentException
{
    /**
     * @param  list<string>  $available
     */
    public static function for(string $operator, array $available): self
    {
        $list = implode(', ', $available);

        return new self(
            "SmartFilter: Unknown operator \"{$operator}\". Available operators: {$list}."
        );
    }
}
