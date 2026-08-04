<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter\Collections;

use Pradeepdev\SmartFilter\DTOs\FilterInput;
use Pradeepdev\SmartFilter\DTOs\RelationFilterInput;
use Pradeepdev\SmartFilter\DTOs\SearchInput;
use Pradeepdev\SmartFilter\DTOs\SortInput;

/**
 * Typed container for all parsed filter information from a single request.
 *
 * This is the output of the Parser and the input to the Pipeline.
 * Keeping it as an immutable-ish value object makes it easy to cache,
 * clone, and inspect during debugging.
 */
final class FilterCollection
{
    /**
     * @param  list<FilterInput>         $filters
     * @param  list<RelationFilterInput> $relationFilters
     * @param  list<SortInput>           $sorts
     */
    public function __construct(
        private array $filters = [],
        private array $relationFilters = [],
        private array $sorts = [],
        private ?SearchInput $search = null,
    ) {}

    /** @return list<FilterInput> */
    public function filters(): array
    {
        return $this->filters;
    }

    /** @return list<RelationFilterInput> */
    public function relationFilters(): array
    {
        return $this->relationFilters;
    }

    /** @return list<SortInput> */
    public function sorts(): array
    {
        return $this->sorts;
    }

    public function search(): ?SearchInput
    {
        return $this->search;
    }

    public function hasFilters(): bool
    {
        return $this->filters !== [];
    }

    public function hasRelationFilters(): bool
    {
        return $this->relationFilters !== [];
    }

    public function hasSorts(): bool
    {
        return $this->sorts !== [];
    }

    public function hasSearch(): bool
    {
        return $this->search !== null;
    }

    public function isEmpty(): bool
    {
        return ! $this->hasFilters()
            && ! $this->hasRelationFilters()
            && ! $this->hasSorts()
            && ! $this->hasSearch();
    }

    /**
     * Return a new collection with an additional flat filter appended.
     */
    public function withFilter(FilterInput $filter): self
    {
        $clone          = clone $this;
        $clone->filters = [...$this->filters, $filter];

        return $clone;
    }

    /**
     * Return a new collection with an additional relation filter appended.
     */
    public function withRelationFilter(RelationFilterInput $filter): self
    {
        $clone                  = clone $this;
        $clone->relationFilters = [...$this->relationFilters, $filter];

        return $clone;
    }

    /**
     * Return a new collection with an additional sort appended.
     */
    public function withSort(SortInput $sort): self
    {
        $clone        = clone $this;
        $clone->sorts = [...$this->sorts, $sort];

        return $clone;
    }

    /**
     * Return a new collection with a search directive set.
     */
    public function withSearch(SearchInput $search): self
    {
        $clone         = clone $this;
        $clone->search = $search;

        return $clone;
    }
}
