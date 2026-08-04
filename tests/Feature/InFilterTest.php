<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Pradeepdev\SmartFilter\Tests\Models\User;

beforeEach(function (): void {
    User::create(['name' => 'Alice', 'email' => 'alice@example.com', 'country' => 'india']);
    User::create(['name' => 'Bob',   'email' => 'bob@example.com',   'country' => 'usa']);
    User::create(['name' => 'Carol', 'email' => 'carol@example.com', 'country' => 'uk']);
});

it('filters with IN operator', function (): void {
    $request = Request::create('/users', 'GET', ['country' => 'in(india,usa)']);

    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(2)
        ->and($results->pluck('country')->toArray())->toContain('india', 'usa');
});

it('filters with NOT IN operator', function (): void {
    $request = Request::create('/users', 'GET', ['country' => 'not_in(india,usa)']);

    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->country)->toBe('uk');
});

it('IN with a single value works correctly', function (): void {
    $request = Request::create('/users', 'GET', ['country' => 'in(uk)']);

    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Carol');
});
