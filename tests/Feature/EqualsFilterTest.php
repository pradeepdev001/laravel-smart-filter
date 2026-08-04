<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Pradeepdev\SmartFilter\Tests\Models\User;

beforeEach(function (): void {
    User::create(['name' => 'Alice', 'email' => 'alice@example.com', 'status' => 'active', 'age' => 30]);
    User::create(['name' => 'Bob',   'email' => 'bob@example.com',   'status' => 'inactive', 'age' => 25]);
    User::create(['name' => 'Carol', 'email' => 'carol@example.com', 'status' => 'active', 'age' => 35]);
});

it('filters by exact equals', function (): void {
    $request = Request::create('/users', 'GET', ['status' => 'active']);

    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(2)
        ->and($results->pluck('name')->toArray())->toContain('Alice', 'Carol');
});

it('returns all records when no filter matches', function (): void {
    $request = Request::create('/users', 'GET', ['status' => 'banned']);

    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(0);
});

it('filters a single record by unique field', function (): void {
    $request = Request::create('/users', 'GET', ['email' => 'bob@example.com']);

    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Bob');
});

it('filters by not equals using != operator', function (): void {
    $request = Request::create('/users', 'GET', ['status!=' => 'active']);

    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Bob');
});
