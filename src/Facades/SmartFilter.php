<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter\Facades;

use Illuminate\Support\Facades\Facade;
use Pradeepdev\SmartFilter\SmartFilterManager;

/**
 * Facade for the SmartFilterManager.
 *
 * Provides the static API for extending SmartFilter with custom operators:
 *
 *   SmartFilter::extend('starts_with', new StartsWithOperator());
 *   SmartFilter::extend('ends_with', fn () => new EndsWithOperator());
 *
 * @method static void extend(string $name, \Pradeepdev\SmartFilter\Contracts\OperatorContract|callable $operator)
 * @method static \Pradeepdev\SmartFilter\Contracts\OperatorRegistryContract registry()
 *
 * @see SmartFilterManager
 */
final class SmartFilter extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SmartFilterManager::class;
    }
}
