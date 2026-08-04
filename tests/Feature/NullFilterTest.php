<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Pradeepdev\SmartFilter\Tests\Models\User;

beforeEach(function (): void {
    User::create(['name' => 'Alice', 'email' => 'alice@example.com', 'deleted_at' => null]);
    User::create(['name' => 'Bob',   'email' => 'bob@example.com',   'deleted_at' => now()]);
    User::create(['name' => 'Carol', 'email' => 'carol@example.com', 'deleted_at' => null]);
});

it('filters where field IS NULL', function (): void {
    $request = Request::create('/users', 'GET', ['deleted_at' => 'null']);

    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(2)
        ->and($results->pluck('name')->toArray())->toContain('Alice', 'Carol');
});

it('filters where field IS NOT NULL', function (): void {
    $request = Request::create('/users', 'GET', ['deleted_at' => 'not_null']);

    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Bob');
});
