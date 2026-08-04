<?php

declare(strict_types=1);

use Pradeepdev\SmartFilter\Collections\FilterCollection;
use Pradeepdev\SmartFilter\DTOs\FilterInput;
use Pradeepdev\SmartFilter\DTOs\SearchInput;
use Pradeepdev\SmartFilter\DTOs\SortInput;
use Pradeepdev\SmartFilter\Enums\Operator;
use Pradeepdev\SmartFilter\Enums\SortDirection;

it('starts empty', function (): void {
    $collection = new FilterCollection();

    expect($collection->isEmpty())->toBeTrue()
        ->and($collection->hasFilters())->toBeFalse()
        ->and($collection->hasSorts())->toBeFalse()
        ->and($collection->hasSearch())->toBeFalse();
});

it('adds a filter immutably', function (): void {
    $collection = new FilterCollection();
    $input      = new FilterInput('name', Operator::Equals->value, 'Alice');

    $new = $collection->withFilter($input);

    expect($collection->hasFilters())->toBeFalse()   // original unchanged
        ->and($new->hasFilters())->toBeTrue()
        ->and($new->filters())->toHaveCount(1);
});

it('adds a sort immutably', function (): void {
    $collection = new FilterCollection();
    $sort       = new SortInput('name', SortDirection::Asc);

    $new = $collection->withSort($sort);

    expect($collection->hasSorts())->toBeFalse()
        ->and($new->hasSorts())->toBeTrue();
});

it('adds a search immutably', function (): void {
    $collection = new FilterCollection();
    $search     = new SearchInput('john', ['name', 'email']);

    $new = $collection->withSearch($search);

    expect($collection->hasSearch())->toBeFalse()
        ->and($new->hasSearch())->toBeTrue()
        ->and($new->search()->term)->toBe('john');
});

it('is not empty when it has a filter', function (): void {
    $collection = (new FilterCollection())->withFilter(
        new FilterInput('name', Operator::Equals->value, 'Alice')
    );

    expect($collection->isEmpty())->toBeFalse();
});
