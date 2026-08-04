<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Pradeepdev\SmartFilter\Tests\Models\User;

beforeEach(function (): void {
    User::create(['name' => 'John Smith',  'email' => 'john@example.com']);
    User::create(['name' => 'Johnny Depp', 'email' => 'johnny@example.com']);
    User::create(['name' => 'Jane Doe',    'email' => 'jane@example.com']);
});

it('filters with LIKE operator using tilde syntax', function (): void {
    $request = Request::create('/users', 'GET', ['name~' => 'john']);

    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(2)
        ->and($results->pluck('name')->toArray())->toContain('John Smith', 'Johnny Depp');
});

it('LIKE is case-insensitive on SQLite', function (): void {
    $request = Request::create('/users', 'GET', ['name~' => 'JANE']);

    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Jane Doe');
});

it('filters with NOT LIKE operator', function (): void {
    $request = Request::create('/users', 'GET', ['name!~' => 'john']);

    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Jane Doe');
});

it('returns no results when LIKE matches nothing', function (): void {
    $request = Request::create('/users', 'GET', ['name~' => 'zzznomatch']);

    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(0);
});
