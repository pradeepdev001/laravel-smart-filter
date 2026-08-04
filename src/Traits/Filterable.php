<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Pradeepdev\SmartFilter\Builders\FilterBuilder;
use Pradeepdev\SmartFilter\Contracts\OperatorRegistryContract;
use Pradeepdev\SmartFilter\Support\FieldGuard;
use Pradeepdev\SmartFilter\Support\OperatorRegistry;

/**
 * Add this trait to any Eloquent model to enable SmartFilter.
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
     * @param  Builder       $query
     * @param  Request|null  $request  Inject a custom Request for testing.
     */
    public function scopeSmartFilter(Builder $query, ?Request $request = null): Builder
    {
        $request = $request ?? app(Request::class);

        // Read configuration from model properties (if declared) or fall back to defaults.
        $filterable   = property_exists($this, 'filterable')   ? $this->filterable   : [];
        $filterIgnore = property_exists($this, 'filterIgnore') ? $this->filterIgnore : [];
        $filterAliases= property_exists($this, 'filterAliases')? $this->filterAliases: [];
        $searchable   = property_exists($this, 'searchable')   ? $this->searchable   : [];
        $filterStrict = property_exists($this, 'filterStrict') ? $this->filterStrict : false;

        // Always build the parser with model-level configuration so that
        // $searchable and $filterIgnore from the model are respected.
        // We pull sort/search param names from config so they remain consistent.
        $parser = new \Pradeepdev\SmartFilter\Parser\RequestParser(
            ignoredFields: $filterIgnore,
            searchableFields: $searchable,
            sortParam: config('smart-filter.sort_param', 'sort'),
            searchParam: config('smart-filter.search_param', 'search'),
        );

        /** @var OperatorRegistry $registry */
        $registry = app()->bound(\Pradeepdev\SmartFilter\Contracts\OperatorRegistryContract::class)
            ? app(\Pradeepdev\SmartFilter\Contracts\OperatorRegistryContract::class)
            : \Pradeepdev\SmartFilter\SmartFilterServiceProvider::buildDefaultRegistry();

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
