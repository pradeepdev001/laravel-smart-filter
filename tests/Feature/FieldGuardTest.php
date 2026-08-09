<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Pradeepdev\SmartFilter\Exceptions\InvalidFilterFieldException;
use Pradeepdev\SmartFilter\Tests\Models\User;

beforeEach(function (): void {
    User::create(['name' => 'Alice', 'email' => 'alice@example.com', 'status' => 'active']);
    User::create(['name' => 'Bob',   'email' => 'bob@example.com',   'status' => 'inactive']);
});

it('silently ignores fields not in the allowed list', function (): void {
    // The User model only allows specific fields.
    // 'unknown_field' is not in $filterable, so it should be ignored — not throw.
    $request = Request::create('/users', 'GET', [
        'unknown_field' => 'some_value',
        'status' => 'active',
    ]);

    $results = User::smartFilter($request)->get();

    // Still filters by status correctly
    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Alice');
});

it('silently drops the sort key itself from filters', function (): void {
    $request = Request::create('/users', 'GET', ['sort' => 'name']);

    // Should sort, not filter by "sort" as a field
    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(2);
});

it('strict mode throws for a field not in allowed list', function (): void {
    $model = new class extends User
    {
        protected bool $filterStrict = true;

        protected array $filterable = ['name'];
    };

    $request = Request::create('/users', 'GET', ['status' => 'active']);

    expect(fn () => $model->newQuery()->smartFilter($request)->get())
        ->toThrow(InvalidFilterFieldException::class);
});

it('resolves alias to the real column', function (): void {
    // 'active' is aliased to 'is_active' in the User model
    $request = Request::create('/users', 'GET', ['active' => '1']);

    // Should run without error — the guard resolves alias before checking
    $results = User::smartFilter($request)->get();

    expect($results)->toBeCollection();
});
