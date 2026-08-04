<?php

declare(strict_types=1);

use Pradeepdev\SmartFilter\DTOs\RelationFilterInput;
use Pradeepdev\SmartFilter\Enums\Operator;

it('returns the root relation name', function (): void {
    $input = new RelationFilterInput(['posts', 'comments'], 'title', Operator::Equals->value, 'hello');

    expect($input->rootRelation())->toBe('posts');
});

it('removes the root relation on withoutRootRelation', function (): void {
    $input  = new RelationFilterInput(['posts', 'comments'], 'title', Operator::Equals->value, 'hello');
    $nested = $input->withoutRootRelation();

    expect($nested->relation)->toBe(['comments'])
        ->and($nested->field)->toBe('title');
});

it('identifies an existence check operator', function (): void {
    $has      = new RelationFilterInput(['posts'], null, 'has', null);
    $doesnt   = new RelationFilterInput(['posts'], null, 'doesnt_have', null);
    $notExist = new RelationFilterInput(['posts'], 'title', Operator::Equals->value, 'test');

    expect($has->isExistenceCheck())->toBeTrue()
        ->and($doesnt->isExistenceCheck())->toBeTrue()
        ->and($notExist->isExistenceCheck())->toBeFalse();
});

it('is immutable — withoutRootRelation returns a new instance', function (): void {
    $input  = new RelationFilterInput(['posts'], 'title', Operator::Equals->value, 'hello');
    $nested = $input->withoutRootRelation();

    expect($input)->not->toBe($nested)
        ->and($input->relation)->toBe(['posts'])
        ->and($nested->relation)->toBe([]);
});
