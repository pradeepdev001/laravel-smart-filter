<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Pradeepdev\SmartFilter\Tests\Models\User;

beforeEach(function (): void {
    User::create(['name' => 'John Smith',   'email' => 'john@example.com']);
    User::create(['name' => 'Jane Doe',     'email' => 'jane@example.com']);
    User::create(['name' => 'Bob Johnson',  'email' => 'bob@johnson.com']);
});

it('searches across configured searchable fields', function (): void {
    $request = Request::create('/users', 'GET', ['search' => 'john']);

    $results = User::smartFilter($request)->get();

    // "John Smith" matches on name; "Bob Johnson" matches on both name and email.
    // Jane Doe does not match. That's 2 distinct rows.
    expect($results)->toHaveCount(2)
        ->and($results->pluck('name')->toArray())->toContain('John Smith', 'Bob Johnson');
});

it('search with no matches returns empty', function (): void {
    $request = Request::create('/users', 'GET', ['search' => 'zzznomatch999']);

    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(0);
});

it('search can combine with other filters', function (): void {
    $request = Request::create('/users', 'GET', [
        'search' => 'john',
        'name~'  => 'Bob',
    ]);

    // search gives us 2 results (John Smith, Bob Johnson), then name~ 'Bob' narrows to 1
    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Bob Johnson');
});
