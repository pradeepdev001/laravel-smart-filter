<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Pradeepdev\SmartFilter\Enums\Operator;
use Pradeepdev\SmartFilter\Enums\SortDirection;
use Pradeepdev\SmartFilter\Parser\RequestParser;

it('parses a simple equals filter', function (): void {
    $request = Request::create('/', 'GET', ['status' => 'active']);
    $parser  = new RequestParser();

    $collection = $parser->parse($request);

    expect($collection->filters())->toHaveCount(1)
        ->and($collection->filters()[0]->field)->toBe('status')
        ->and($collection->filters()[0]->operator)->toBe(Operator::Equals->value)
        ->and($collection->filters()[0]->value)->toBe('active');
});

it('parses a greater-than filter from inline key operator', function (): void {
    $request = Request::create('/', 'GET', ['age>' => '25']);
    $parser  = new RequestParser();

    $collection = $parser->parse($request);

    expect($collection->filters()[0]->operator)->toBe(Operator::GreaterThan->value)
        ->and($collection->filters()[0]->value)->toBe('25');
});

it('parses ascending sort', function (): void {
    $request = Request::create('/', 'GET', ['sort' => 'name']);
    $parser  = new RequestParser();

    $collection = $parser->parse($request);

    expect($collection->sorts())->toHaveCount(1)
        ->and($collection->sorts()[0]->field)->toBe('name')
        ->and($collection->sorts()[0]->direction)->toBe(SortDirection::Asc);
});

it('parses descending sort with minus prefix', function (): void {
    $request = Request::create('/', 'GET', ['sort' => '-created_at']);
    $parser  = new RequestParser();

    $collection = $parser->parse($request);

    expect($collection->sorts()[0]->direction)->toBe(SortDirection::Desc);
});

it('parses multi-column sort', function (): void {
    $request = Request::create('/', 'GET', ['sort' => '-created_at,name']);
    $parser  = new RequestParser();

    $collection = $parser->parse($request);

    expect($collection->sorts())->toHaveCount(2)
        ->and($collection->sorts()[0]->direction)->toBe(SortDirection::Desc)
        ->and($collection->sorts()[1]->direction)->toBe(SortDirection::Asc);
});

it('parses IN function value', function (): void {
    $request = Request::create('/', 'GET', ['country' => 'in(india,usa)']);
    $parser  = new RequestParser();

    $collection = $parser->parse($request);

    expect($collection->filters()[0]->operator)->toBe(Operator::In->value)
        ->and($collection->filters()[0]->value)->toBe(['india', 'usa']);
});

it('parses BETWEEN function value', function (): void {
    $request = Request::create('/', 'GET', ['price' => 'between(100,500)']);
    $parser  = new RequestParser();

    $collection = $parser->parse($request);

    expect($collection->filters()[0]->operator)->toBe(Operator::Between->value)
        ->and($collection->filters()[0]->value)->toBe(['100', '500']);
});

it('parses null keyword as IS NULL operator', function (): void {
    $request = Request::create('/', 'GET', ['deleted_at' => 'null']);
    $parser  = new RequestParser();

    $collection = $parser->parse($request);

    expect($collection->filters()[0]->operator)->toBe(Operator::IsNull->value)
        ->and($collection->filters()[0]->value)->toBeNull();
});

it('parses not_null keyword as IS NOT NULL operator', function (): void {
    $request = Request::create('/', 'GET', ['deleted_at' => 'not_null']);
    $parser  = new RequestParser();

    $collection = $parser->parse($request);

    expect($collection->filters()[0]->operator)->toBe(Operator::IsNotNull->value);
});

it('parses "true" as a boolean filter', function (): void {
    $request = Request::create('/', 'GET', ['is_active' => 'true']);
    $parser  = new RequestParser();

    $collection = $parser->parse($request);

    expect($collection->filters()[0]->operator)->toBe(Operator::Boolean->value);
});

it('parses search term', function (): void {
    $request = Request::create('/', 'GET', ['search' => 'john']);
    $parser  = new RequestParser(searchableFields: ['name', 'email']);

    $collection = $parser->parse($request);

    expect($collection->search())->not->toBeNull()
        ->and($collection->search()->term)->toBe('john')
        ->and($collection->search()->fields)->toBe(['name', 'email']);
});

it('excludes page, per_page, limit from filters', function (): void {
    $request = Request::create('/', 'GET', [
        'page'     => '1',
        'per_page' => '15',
        'limit'    => '10',
        'name'     => 'Alice',
    ]);
    $parser = new RequestParser();

    $collection = $parser->parse($request);

    expect($collection->filters())->toHaveCount(1)
        ->and($collection->filters()[0]->field)->toBe('name');
});

it('returns empty collection for empty request', function (): void {
    $request = Request::create('/', 'GET', []);
    $parser  = new RequestParser();

    $collection = $parser->parse($request);

    expect($collection->isEmpty())->toBeTrue();
});
