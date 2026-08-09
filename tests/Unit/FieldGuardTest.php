<?php

declare(strict_types=1);

use Pradeepdev\SmartFilter\DTOs\FilterInput;
use Pradeepdev\SmartFilter\DTOs\SortInput;
use Pradeepdev\SmartFilter\Enums\Operator;
use Pradeepdev\SmartFilter\Enums\SortDirection;
use Pradeepdev\SmartFilter\Exceptions\InvalidFilterFieldException;
use Pradeepdev\SmartFilter\Support\FieldGuard;

it('allows any field when no allowed list is defined', function (): void {
    $guard = new FieldGuard;
    $input = new FilterInput('name', Operator::Equals->value, 'Alice');

    expect($guard->resolveFilter($input))->not->toBeNull();
});

it('allows a field that is in the allowed list', function (): void {
    $guard = new FieldGuard(allowedFields: ['name', 'email']);
    $input = new FilterInput('name', Operator::Equals->value, 'Alice');

    expect($guard->resolveFilter($input))->not->toBeNull();
});

it('returns null for a field not in the allowed list (non-strict)', function (): void {
    $guard = new FieldGuard(allowedFields: ['name']);
    $input = new FilterInput('password', Operator::Equals->value, 'secret');

    expect($guard->resolveFilter($input))->toBeNull();
});

it('throws in strict mode for a field not in the allowed list', function (): void {
    $guard = new FieldGuard(allowedFields: ['name'], strictMode: true);
    $input = new FilterInput('password', Operator::Equals->value, 'secret');

    expect(fn () => $guard->resolveFilter($input))
        ->toThrow(InvalidFilterFieldException::class);
});

it('returns null for an ignored field (non-strict)', function (): void {
    $guard = new FieldGuard(ignoredFields: ['password']);
    $input = new FilterInput('password', Operator::Equals->value, 'secret');

    expect($guard->resolveFilter($input))->toBeNull();
});

it('throws in strict mode for an ignored field', function (): void {
    $guard = new FieldGuard(ignoredFields: ['password'], strictMode: true);
    $input = new FilterInput('password', Operator::Equals->value, 'secret');

    expect(fn () => $guard->resolveFilter($input))
        ->toThrow(InvalidFilterFieldException::class);
});

it('resolves an alias to the real column', function (): void {
    $guard = new FieldGuard(aliases: ['city' => 'address_city']);
    $input = new FilterInput('city', Operator::Equals->value, 'London');
    $resolved = $guard->resolveFilter($input);

    expect($resolved)->not->toBeNull()
        ->and($resolved->field)->toBe('address_city');
});

it('resolves sort alias', function (): void {
    $guard = new FieldGuard(aliases: ['created' => 'created_at']);
    $input = new SortInput('created', SortDirection::Asc);
    $resolved = $guard->resolveSort($input);

    expect($resolved)->not->toBeNull()
        ->and($resolved->field)->toBe('created_at');
});
