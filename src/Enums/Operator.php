<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter\Enums;

/**
 * Canonical operator names used throughout the package.
 *
 * Using an enum prevents magic strings from leaking across the codebase
 * and gives IDEs and static analysers something to work with.
 */
enum Operator: string
{
    // Equality
    case Equals        = 'eq';
    case NotEquals     = 'neq';

    // Comparison
    case GreaterThan        = 'gt';
    case GreaterThanOrEqual = 'gte';
    case LessThan           = 'lt';
    case LessThanOrEqual    = 'lte';

    // String matching
    case Like        = 'like';
    case NotLike     = 'not_like';

    // Set membership
    case In    = 'in';
    case NotIn = 'not_in';

    // Range
    case Between    = 'between';
    case NotBetween = 'not_between';

    // Null checks
    case IsNull    = 'null';
    case IsNotNull = 'not_null';

    // Boolean
    case Boolean = 'bool';
}
