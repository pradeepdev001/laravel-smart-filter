<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter\DTOs;

/**
 * Immutable value object representing a full-text search request.
 *
 * @param  list<string>  $fields  The columns to search across.
 */
final class SearchInput
{
    /**
     * @param  list<string>  $fields
     */
    public function __construct(
        public readonly string $term,
        public readonly array $fields,
    ) {}
}
