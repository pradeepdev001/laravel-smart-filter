<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Pradeepdev\SmartFilter\Tests\Models\User;

beforeEach(function (): void {
    User::create(['name' => 'Carol', 'email' => 'carol@example.com', 'age' => 35]);
    User::create(['name' => 'Alice', 'email' => 'alice@example.com', 'age' => 20]);
    User::create(['name' => 'Bob',   'email' => 'bob@example.com',   'age' => 30]);
});

it('sorts ascending by name', function (): void {
    $request = Request::create('/users', 'GET', ['sort' => 'name']);

    $results = User::smartFilter($request)->get();

    expect($results->pluck('name')->toArray())->toBe(['Alice', 'Bob', 'Carol']);
});

it('sorts descending by name using minus prefix', function (): void {
    $request = Request::create('/users', 'GET', ['sort' => '-name']);

    $results = User::smartFilter($request)->get();

    expect($results->pluck('name')->toArray())->toBe(['Carol', 'Bob', 'Alice']);
});

it('sorts ascending by age', function (): void {
    $request = Request::create('/users', 'GET', ['sort' => 'age']);

    $results = User::smartFilter($request)->get();

    expect($results->pluck('age')->toArray())->toBe([20, 30, 35]);
});

it('sorts descending by age', function (): void {
    $request = Request::create('/users', 'GET', ['sort' => '-age']);

    $results = User::smartFilter($request)->get();

    expect($results->pluck('age')->toArray())->toBe([35, 30, 20]);
});

it('supports multi-column sort with comma-separated values', function (): void {
    // Add a duplicate-age user to make multi-sort observable
    User::create(['name' => 'Zara', 'email' => 'zara@example.com', 'age' => 20]);

    $request = Request::create('/users', 'GET', ['sort' => 'age,-name']);

    $results = User::smartFilter($request)->get();

    $names = $results->pluck('name')->toArray();

    // Both 20-year-olds appear first, Zara before Alice (desc name)
    expect($names[0])->toBe('Zara')
        ->and($names[1])->toBe('Alice');
});
