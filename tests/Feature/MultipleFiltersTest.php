<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Pradeepdev\SmartFilter\Tests\Models\User;

beforeEach(function (): void {
    User::create(['name' => 'Alice', 'email' => 'alice@example.com', 'status' => 'active',   'age' => 25, 'country' => 'india']);
    User::create(['name' => 'Bob',   'email' => 'bob@example.com',   'status' => 'active',   'age' => 35, 'country' => 'usa']);
    User::create(['name' => 'Carol', 'email' => 'carol@example.com', 'status' => 'inactive', 'age' => 28, 'country' => 'india']);
    User::create(['name' => 'Dave',  'email' => 'dave@example.com',  'status' => 'active',   'age' => 22, 'country' => 'uk']);
});

it('applies multiple filters together with AND logic', function (): void {
    $request = Request::create('/users', 'GET', [
        'status' => 'active',
        'country' => 'india',
    ]);

    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Alice');
});

it('combines comparison and equals filters', function (): void {
    $request = Request::create('/users', 'GET', [
        'status' => 'active',
        'age>=' => '30',
    ]);

    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Bob');
});

it('combines IN and comparison filters', function (): void {
    $request = Request::create('/users', 'GET', [
        'country' => 'in(india,usa)',
        'age>=' => '30',
    ]);

    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Bob');
});

it('combines filters and sort together', function (): void {
    $request = Request::create('/users', 'GET', [
        'status' => 'active',
        'sort' => 'age',
    ]);

    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(3)
        ->and($results->pluck('name')->toArray())->toBe(['Dave', 'Alice', 'Bob']);
});
