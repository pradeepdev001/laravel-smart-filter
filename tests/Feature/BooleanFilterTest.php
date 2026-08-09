<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Pradeepdev\SmartFilter\Tests\Models\User;

beforeEach(function (): void {
    User::create(['name' => 'Alice', 'email' => 'alice@example.com', 'is_active' => true]);
    User::create(['name' => 'Bob',   'email' => 'bob@example.com',   'is_active' => false]);
    User::create(['name' => 'Carol', 'email' => 'carol@example.com', 'is_active' => true]);
});

it('filters with boolean true using "true"', function (): void {
    $request = Request::create('/users', 'GET', ['is_active' => 'true']);

    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(2)
        ->and($results->pluck('name')->toArray())->toContain('Alice', 'Carol');
});

it('filters with boolean false using "false"', function (): void {
    $request = Request::create('/users', 'GET', ['is_active' => 'false']);

    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Bob');
});

it('filters with boolean true using "1"', function (): void {
    $request = Request::create('/users', 'GET', ['is_active' => '1']);

    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(2);
});

it('filters with boolean false using "0"', function (): void {
    $request = Request::create('/users', 'GET', ['is_active' => '0']);

    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(1);
});

it('filters using alias for is_active', function (): void {
    // 'active' is aliased to 'is_active' in the User model
    $request = Request::create('/users', 'GET', ['active' => 'true']);

    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(2);
});
