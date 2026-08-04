<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Pradeepdev\SmartFilter\Tests\Models\User;

beforeEach(function (): void {
    User::create(['name' => 'Alice', 'email' => 'alice@example.com']);
    User::create(['name' => 'Bob',   'email' => 'bob@example.com']);
});

it('does not execute SQL injection via field value', function (): void {
    // If binding wasn't used this would drop a table or error badly
    $request = Request::create('/users', 'GET', ['name' => "'; DROP TABLE users; --"]);

    // Should run without exception — Eloquent uses PDO parameter binding
    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(0); // No match, but table still intact
});

it('does not execute SQL injection via operator value', function (): void {
    $request = Request::create('/users', 'GET', ['name~' => "' OR '1'='1"]);

    $results = User::smartFilter($request)->get();

    // LIKE "%' OR '1'='1%" matches nothing in SQLite safely
    expect($results)->toHaveCount(0);
});

it('strips null bytes from filter values', function (): void {
    $request = Request::create('/users', 'GET', ['name' => "Alice\x00"]);

    // Should run without exception, null byte stripped
    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Alice');
});

it('ignores reserved query params page and per_page', function (): void {
    $request = Request::create('/users', 'GET', [
        'page'     => '2',
        'per_page' => '10',
        'name'     => 'Alice',
    ]);

    $results = User::smartFilter($request)->get();

    // 'page' and 'per_page' should not become filter conditions
    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Alice');
});

it('ignores nested array parameters', function (): void {
    // ?filter[name]=Alice — arrays in params are skipped in Phase 1
    $request = Request::create('/users', 'GET', ['filter' => ['name' => 'Alice']]);

    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(2); // No filter applied, all returned
});
