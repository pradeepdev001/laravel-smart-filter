<?php

declare(strict_types=1);

use Pradeepdev\SmartFilter\Exceptions\UnknownOperatorException;
use Pradeepdev\SmartFilter\Operators\EqualsOperator;
use Pradeepdev\SmartFilter\Operators\LikeOperator;
use Pradeepdev\SmartFilter\Support\OperatorRegistry;

it('resolves a registered operator by name', function (): void {
    $registry = new OperatorRegistry();
    $registry->register(new EqualsOperator());

    $operator = $registry->resolve('eq');

    expect($operator)->toBeInstanceOf(EqualsOperator::class);
});

it('throws UnknownOperatorException for an unregistered name', function (): void {
    $registry = new OperatorRegistry();

    expect(fn () => $registry->resolve('nonexistent'))
        ->toThrow(UnknownOperatorException::class);
});

it('returns true for has() on a registered operator', function (): void {
    $registry = new OperatorRegistry();
    $registry->register(new LikeOperator());

    expect($registry->has('like'))->toBeTrue();
});

it('returns false for has() on an unregistered operator', function (): void {
    $registry = new OperatorRegistry();

    expect($registry->has('like'))->toBeFalse();
});

it('lists all registered operator names', function (): void {
    $registry = new OperatorRegistry();
    $registry->register(new EqualsOperator());
    $registry->register(new LikeOperator());

    expect($registry->names())->toContain('eq', 'like');
});

it('registers an operator via a callable factory', function (): void {
    $registry = new OperatorRegistry();
    $registry->register(fn () => new EqualsOperator());

    expect($registry->has('eq'))->toBeTrue();
});

it('exception message lists available operators', function (): void {
    $registry = new OperatorRegistry();
    $registry->register(new EqualsOperator());

    try {
        $registry->resolve('nonexistent');
    } catch (UnknownOperatorException $e) {
        expect($e->getMessage())->toContain('eq');
    }
});
