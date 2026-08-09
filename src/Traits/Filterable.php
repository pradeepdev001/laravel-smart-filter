<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Pradeepdev\SmartFilter\Builders\FilterBuilder;
use Pradeepdev\SmartFilter\Contracts\OperatorRegistryContract;
use Pradeepdev\SmartFilter\Parser\RequestParser;
use Pradeepdev\SmartFilter\SmartFilterServiceProvider;
use Pradeepdev\SmartFilter\Support\FieldGuard;
use Pradeepdev\SmartFilter\Support\OperatorRegistry;

/**
 * Add this trait to any Eloquent model to enable SmartFilter.
 *
 * @mixin Model
 *
 * Usage:
 *   User::smartFilter()->paginate();
 *   User::query()->smartFilter()->paginate();
 *
 * The trait registers a `smartFilter` local scope. When called, it:
 *   1. Resolves the parser and registry from the container (or falls back to defaults)
 *   2. Parses the current request
 *   3. Builds the query
 *
 * To restrict filterable fields, declare on the model:
 *   protected array $filterable = ['name', 'email', 'status'];
 *
 * To ignore fields from filtering:
 *   protected array $filterIgnore = ['password'];
 *
 * To configure aliases:
 *   protected array $filterAliases = ['city' => 'address_city'];
 *
 * To configure searchable fields:
 *   protected array $searchable = ['name', 'email'];
 *
 * NOTE: These properties must be declared on the model class, not this trait,
 * to avoid PHP trait property composition conflicts.
 */
trait Filterable
{
    /**
     * Local Eloquent scope — the primary entry point.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  Request|null  $request  Inject a custom Request for testing.
     * @return Builder<TModel>
     */
    public function scopeSmartFilter(Builder $query, ?Request $request = null): Builder
    {
        $request = $request ?? app(Request::class);
        /** @var array<string, mixed> $properties */
        $properties = get_object_vars($this);

        // Read configuration from model properties (if declared) or fall back to defaults.
        /** @var list<string> $filterable */
        $filterable = isset($properties['filterable']) && is_array($properties['filterable'])
            ? array_values($properties['filterable'])
            : [];
        /** @var list<string> $filterIgnore */
        $filterIgnore = isset($properties['filterIgnore']) && is_array($properties['filterIgnore'])
            ? array_values($properties['filterIgnore'])
            : [];
        /** @var array<string, string> $filterAliases */
        $filterAliases = isset($properties['filterAliases']) && is_array($properties['filterAliases'])
            ? $properties['filterAliases']
            : [];
        /** @var list<string> $searchable */
        $searchable = isset($properties['searchable']) && is_array($properties['searchable'])
            ? array_values($properties['searchable'])
            : [];
        $filterStrict = isset($properties['filterStrict']) && is_bool($properties['filterStrict'])
            ? $properties['filterStrict']
            : false;

        // Always build the parser with model-level configuration so that
        // $searchable and $filterIgnore from the model are respected.
        // We pull sort/search param names from config so they remain consistent.
        $parser = new RequestParser(
            ignoredFields: $filterIgnore,
            searchableFields: $searchable,
            sortParam: config('smart-filter.sort_param', 'sort'),
            searchParam: config('smart-filter.search_param', 'search'),
        );

        /** @var OperatorRegistry $registry */
        $registry = app()->bound(OperatorRegistryContract::class)
            ? app(OperatorRegistryContract::class)
            : SmartFilterServiceProvider::buildDefaultRegistry();

        $guard = new FieldGuard(
            allowedFields: $filterable,
            ignoredFields: $filterIgnore,
            aliases: $filterAliases,
            strictMode: $filterStrict,
        );

        $collection = $parser->parse($request);

        return (new FilterBuilder($registry, $guard))->apply($query, $collection);
    }
}
