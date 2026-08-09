<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter\Support;

use Pradeepdev\SmartFilter\DTOs\FilterInput;
use Pradeepdev\SmartFilter\DTOs\SortInput;
use Pradeepdev\SmartFilter\Exceptions\InvalidFilterFieldException;

/**
 * Enforces field-level security and resolves aliases.
 *
 * The guard runs before any query is built. It:
 *   1. Resolves aliases  (e.g. "city" → "address.city")
 *   2. Checks against the ignored list (always enforced)
 *   3. Checks against the allowed list (only enforced when non-empty)
 *
 * In strict mode, violations throw exceptions.
 * In non-strict mode (default), invalid fields are silently skipped.
 */
final class FieldGuard
{
    /**
     * @param  list<string>  $allowedFields  Empty means "all fields allowed".
     * @param  list<string>  $ignoredFields  Always blocked regardless of allowed list.
     * @param  array<string, string>  $aliases  Maps request param names to real column names.
     */
    public function __construct(
        private readonly array $allowedFields = [],
        private readonly array $ignoredFields = [],
        private readonly array $aliases = [],
        private readonly bool $strictMode = false,
    ) {}

    /**
     * Validate and resolve a FilterInput.
     * Returns null if the field should be skipped silently (non-strict mode).
     *
     * @throws InvalidFilterFieldException In strict mode.
     */
    public function resolveFilter(FilterInput $input): ?FilterInput
    {
        $field = $this->resolveAlias($input->field);
        $input = $input->withField($field);

        if ($this->isIgnored($field)) {
            if ($this->strictMode) {
                throw InvalidFilterFieldException::fieldIgnored($field);
            }

            return null;
        }

        if (! $this->isAllowed($field)) {
            if ($this->strictMode) {
                throw InvalidFilterFieldException::fieldNotAllowed($field, $this->allowedFields);
            }

            return null;
        }

        return $input;
    }

    /**
     * Validate and resolve a SortInput.
     * Returns null if the field should be skipped silently (non-strict mode).
     *
     * @throws InvalidFilterFieldException In strict mode.
     */
    public function resolveSort(SortInput $input): ?SortInput
    {
        $field = $this->resolveAlias($input->field);

        if ($this->isIgnored($field)) {
            if ($this->strictMode) {
                throw InvalidFilterFieldException::fieldIgnored($field);
            }

            return null;
        }

        if (! $this->isAllowed($field)) {
            if ($this->strictMode) {
                throw InvalidFilterFieldException::fieldNotAllowed($field, $this->allowedFields);
            }

            return null;
        }

        return new SortInput($field, $input->direction);
    }

    // -------------------------------------------------------------------------

    private function resolveAlias(string $field): string
    {
        return $this->aliases[$field] ?? $field;
    }

    private function isIgnored(string $field): bool
    {
        return in_array($field, $this->ignoredFields, true);
    }

    private function isAllowed(string $field): bool
    {
        // Empty allowed list means everything is permitted
        if ($this->allowedFields === []) {
            return true;
        }

        return in_array($field, $this->allowedFields, true);
    }
}
