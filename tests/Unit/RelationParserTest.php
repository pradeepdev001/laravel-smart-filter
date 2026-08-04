<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Pradeepdev\SmartFilter\Enums\Operator;
use Pradeepdev\SmartFilter\Parser\RequestParser;

it('parses a dot-notation param as a relation filter', function (): void {
    $request = Request::create('/', 'GET', ['posts.status' => 'published']);
    $parser  = new RequestParser();

    $collection = $parser->parse($request);

    expect($collection->filters())->toHaveCount(0)
        ->and($collection->relationFilters())->toHaveCount(1);

    $rel = $collection->relationFilters()[0];
    expect($rel->relation)->toBe(['posts'])
        ->and($rel->field)->toBe('status')
        ->and($rel->operator)->toBe(Operator::Equals->value)
        ->and($rel->value)->toBe('published');
});

it('parses a nested dot-notation param', function (): void {
    $request = Request::create('/', 'GET', ['company.address.city' => 'London']);
    $parser  = new RequestParser();

    $collection = $parser->parse($request);
    $rel        = $collection->relationFilters()[0];

    expect($rel->relation)->toBe(['company', 'address'])
        ->and($rel->field)->toBe('city')
        ->and($rel->operator)->toBe(Operator::Equals->value);
});

it('parses a relation filter with inline operator', function (): void {
    $request = Request::create('/', 'GET', ['posts.title~' => 'laravel']);
    $parser  = new RequestParser();

    $collection = $parser->parse($request);
    $rel        = $collection->relationFilters()[0];

    expect($rel->operator)->toBe(Operator::Like->value)
        ->and($rel->value)->toBe('laravel');
});

it('parses a has existence check', function (): void {
    $request = Request::create('/', 'GET', ['posts' => 'has']);
    $parser  = new RequestParser();

    $collection = $parser->parse($request);
    $rel        = $collection->relationFilters()[0];

    expect($rel->relation)->toBe(['posts'])
        ->and($rel->field)->toBeNull()
        ->and($rel->operator)->toBe('has')
        ->and($rel->isExistenceCheck())->toBeTrue();
});

it('parses a doesntHave existence check', function (): void {
    $request = Request::create('/', 'GET', ['posts' => 'doesntHave']);
    $parser  = new RequestParser();

    $collection = $parser->parse($request);
    $rel        = $collection->relationFilters()[0];

    expect($rel->operator)->toBe('doesnt_have')
        ->and($rel->isExistenceCheck())->toBeTrue();
});

it('parses a relation filter with IN operator', function (): void {
    $request = Request::create('/', 'GET', ['roles.name' => 'in(admin,editor)']);
    $parser  = new RequestParser();

    $collection = $parser->parse($request);
    $rel        = $collection->relationFilters()[0];

    expect($rel->operator)->toBe(Operator::In->value)
        ->and($rel->value)->toBe(['admin', 'editor']);
});

it('does not parse flat fields as relation filters', function (): void {
    $request = Request::create('/', 'GET', ['status' => 'active', 'name~' => 'alice']);
    $parser  = new RequestParser();

    $collection = $parser->parse($request);

    expect($collection->filters())->toHaveCount(2)
        ->and($collection->relationFilters())->toHaveCount(0);
});

it('can mix flat and relation filters in the same request', function (): void {
    $request = Request::create('/', 'GET', [
        'status'       => 'active',
        'posts.status' => 'published',
    ]);
    $parser = new RequestParser();

    $collection = $parser->parse($request);

    expect($collection->filters())->toHaveCount(1)
        ->and($collection->relationFilters())->toHaveCount(1);
});
