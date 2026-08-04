<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Pradeepdev\SmartFilter\Tests\Models\User;

beforeEach(function (): void {
    User::create(['name' => 'Alice', 'email' => 'alice@example.com', 'age' => 20, 'price' => 100.00]);
    User::create(['name' => 'Bob',   'email' => 'bob@example.com',   'age' => 30, 'price' => 200.00]);
    User::create(['name' => 'Carol', 'email' => 'carol@example.com', 'age' => 40, 'price' => 300.00]);
});

it('filters with greater than operator', function (): void {
    $request = Request::create('/users', 'GET', ['age>' => '25']);

    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(2)
        ->and($results->pluck('name')->toArray())->toContain('Bob', 'Carol');
});

it('filters with greater than or equal operator', function (): void {
    $request = Request::create('/users', 'GET', ['age>=' => '30']);

    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(2)
        ->and($results->pluck('name')->toArray())->toContain('Bob', 'Carol');
});

it('filters with less than operator', function (): void {
    $request = Request::create('/users', 'GET', ['age<' => '30']);

    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Alice');
});

it('filters with less than or equal operator', function (): void {
    $request = Request::create('/users', 'GET', ['age<=' => '30']);

    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(2)
        ->and($results->pluck('name')->toArray())->toContain('Alice', 'Bob');
});

it('combines two comparison filters', function (): void {
    $request = Request::create('/users', 'GET', ['age>=' => '20', 'price<=' => '200']);

    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(2)
        ->and($results->pluck('name')->toArray())->toContain('Alice', 'Bob');
});
