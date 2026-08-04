<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter\DTOs;

/**
 * Immutable value object representing a filter that targets a relationship.
 *
 * Dot-notation in the URL is parsed into this DTO:
 *   ?posts.status=published  →  relation=['posts'], field='status', operator='eq', value='published'
 *   ?company.address.city=London → relation=['company','address'], field='city', ...
 *   ?posts=has               →  relation=['posts'], field=null, operator='has', value=null
 *   ?posts=doesntHave        →  relation=['posts'], field=null, operator='doesnt_have', value=null
 *
 * @param  list<string>  $relation  The chain of relationship method names to traverse.
 */
final readonly class RelationFilterInput
{
    /**
     * @param  list<string>  $relation
     */
    public function __construct(
        public array $relation,
        public ?string $field,
        public string $operator,
        public mixed $value,
    ) {}

    /**
     * Return the top-level relation name (first segment).
     */
    public function rootRelation(): string
    {
        return $this->relation[0];
    }

    /**
     * Return a new instance with the first relation segment removed.
     * Used for recursive nesting.
     */
    public function withoutRootRelation(): self
    {
        return new self(
            relation: array_slice($this->relation, 1),
            field: $this->field,
            operator: $this->operator,
            value: $this->value,
        );
    }

    /**
     * Whether this is a simple existence check (has / doesntHave).
     */
    public function isExistenceCheck(): bool
    {
        return in_array($this->operator, ['has', 'doesnt_have'], true);
    }
}
