<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter\DTOs;

use Pradeepdev\SmartFilter\Enums\SortDirection;

/**
 * Immutable value object representing a single sort directive.
 *
 * ?sort=-created_at  → field=created_at, direction=DESC
 * ?sort=name         → field=name, direction=ASC
 */
final readonly class SortInput
{
    public function __construct(
        public string $field,
        public SortDirection $direction,
    ) {}

    /**
     * Build a SortInput from a raw sort string (e.g. "-created_at" or "name").
     */
    public static function fromRaw(string $raw): self
    {
        $raw = trim($raw);

        if (str_starts_with($raw, '-')) {
            return new self(
                field: substr($raw, 1),
                direction: SortDirection::Desc,
            );
        }

        return new self(
            field: $raw,
            direction: SortDirection::Asc,
        );
    }
}
