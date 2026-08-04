<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Pradeepdev\SmartFilter\Exceptions\InvalidFilterValueException;
use Pradeepdev\SmartFilter\Tests\Models\User;

beforeEach(function (): void {
    User::create(['name' => 'Alice', 'email' => 'alice@example.com', 'price' => 50.00]);
    User::create(['name' => 'Bob',   'email' => 'bob@example.com',   'price' => 150.00]);
    User::create(['name' => 'Carol', 'email' => 'carol@example.com', 'price' => 300.00]);
    User::create(['name' => 'Dave',  'email' => 'dave@example.com',  'price' => 500.00]);
});

it('filters with BETWEEN operator', function (): void {
    $request = Request::create('/users', 'GET', ['price' => 'between(100,400)']);

    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(2)
        ->and($results->pluck('name')->toArray())->toContain('Bob', 'Carol');
});

it('filters with NOT BETWEEN operator', function (): void {
    $request = Request::create('/users', 'GET', ['price' => 'not_between(100,400)']);

    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(2)
        ->and($results->pluck('name')->toArray())->toContain('Alice', 'Dave');
});

it('BETWEEN includes boundary values', function (): void {
    $request = Request::create('/users', 'GET', ['price' => 'between(150,300)']);

    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(2)
        ->and($results->pluck('name')->toArray())->toContain('Bob', 'Carol');
});

it('throws when BETWEEN has only one value', function (): void {
    $request = Request::create('/users', 'GET', ['price' => 'between(100)']);

    expect(fn () => User::smartFilter($request)->get())
        ->toThrow(InvalidFilterValueException::class);
});
