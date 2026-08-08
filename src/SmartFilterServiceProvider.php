<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter;

use Illuminate\Support\ServiceProvider;
use Pradeepdev\SmartFilter\Builders\FilterBuilder;
use Pradeepdev\SmartFilter\Contracts\OperatorContract;
use Pradeepdev\SmartFilter\Contracts\OperatorRegistryContract;
use Pradeepdev\SmartFilter\Contracts\ParserContract;
use Pradeepdev\SmartFilter\Operators\BetweenOperator;
use Pradeepdev\SmartFilter\Operators\BooleanOperator;
use Pradeepdev\SmartFilter\Operators\EqualsOperator;
use Pradeepdev\SmartFilter\Operators\GreaterThanOperator;
use Pradeepdev\SmartFilter\Operators\GreaterThanOrEqualOperator;
use Pradeepdev\SmartFilter\Operators\InOperator;
use Pradeepdev\SmartFilter\Operators\IsNotNullOperator;
use Pradeepdev\SmartFilter\Operators\IsNullOperator;
use Pradeepdev\SmartFilter\Operators\LessThanOperator;
use Pradeepdev\SmartFilter\Operators\LessThanOrEqualOperator;
use Pradeepdev\SmartFilter\Operators\LikeOperator;
use Pradeepdev\SmartFilter\Operators\NotBetweenOperator;
use Pradeepdev\SmartFilter\Operators\NotEqualsOperator;
use Pradeepdev\SmartFilter\Operators\NotInOperator;
use Pradeepdev\SmartFilter\Operators\NotLikeOperator;
use Pradeepdev\SmartFilter\Parser\RequestParser;
use Pradeepdev\SmartFilter\Support\OperatorRegistry;

final class SmartFilterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/smart-filter.php', 'smart-filter');

        // Bind the registry as a singleton so custom operators persist for
        // the lifetime of the request / application.
        $this->app->singleton(OperatorRegistryContract::class, function (): OperatorRegistry {
            $registry = self::buildDefaultRegistry();

            // Register any operators defined in config
            /** @var array<class-string> $extra */
            $extra = config('smart-filter.operators', []);

            foreach ($extra as $operatorClass) {
                /** @var OperatorContract $operator */
                $operator = new $operatorClass();
                $registry->register($operator);
            }

            return $registry;
        });

        // Bind the parser — consumers can swap this to support different
        // input formats (JSON body, GraphQL variables, etc.)
        $this->app->singleton(ParserContract::class, function (): RequestParser {
            /** @var list<string> $ignored */
            $ignored = array_values(config('smart-filter.ignored_fields', []));

            /** @var list<string> $searchable */
            $searchable = array_values(config('smart-filter.searchable_fields', []));

            return new RequestParser(
                ignoredFields: $ignored,
                searchableFields: $searchable,
                sortParam: config('smart-filter.sort_param', 'sort'),
                searchParam: config('smart-filter.search_param', 'search'),
            );
        });

        // Bind the Manager (used by the Facade)
        $this->app->singleton(SmartFilterManager::class, function (): SmartFilterManager {
            return new SmartFilterManager(
                registry: $this->app->make(OperatorRegistryContract::class),
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/smart-filter.php' => config_path('smart-filter.php'),
            ], 'smart-filter-config');
        }
    }

    /**
     * Build and return a registry pre-loaded with all built-in operators.
     *
     * This static factory is used both by the service provider and by the
     * Filterable trait's fallback when no container is available.
     */
    public static function buildDefaultRegistry(): OperatorRegistry
    {
        $registry = new OperatorRegistry();

        $registry->register(new EqualsOperator());
        $registry->register(new NotEqualsOperator());
        $registry->register(new GreaterThanOperator());
        $registry->register(new GreaterThanOrEqualOperator());
        $registry->register(new LessThanOperator());
        $registry->register(new LessThanOrEqualOperator());
        $registry->register(new LikeOperator());
        $registry->register(new NotLikeOperator());
        $registry->register(new InOperator());
        $registry->register(new NotInOperator());
        $registry->register(new BetweenOperator());
        $registry->register(new NotBetweenOperator());
        $registry->register(new IsNullOperator());
        $registry->register(new IsNotNullOperator());
        $registry->register(new BooleanOperator());

        return $registry;
    }
}
