<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter\Parser;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Pradeepdev\SmartFilter\Collections\FilterCollection;
use Pradeepdev\SmartFilter\Contracts\ParserContract;
use Pradeepdev\SmartFilter\DTOs\FilterInput;
use Pradeepdev\SmartFilter\DTOs\SearchInput;
use Pradeepdev\SmartFilter\DTOs\SortInput;
use Pradeepdev\SmartFilter\Enums\Operator;

/**
 * Parses URL query parameters into a structured FilterCollection.
 *
 * Supported URL syntax:
 *   ?status=active              → equals
 *   ?age>25                     → greater than
 *   ?price>=100                 → greater than or equal
 *   ?price<500                  → less than
 *   ?price<=500                 → less than or equal
 *   ?name~john                  → LIKE %john%
 *   ?name!~john                 → NOT LIKE %john%
 *   ?status!=active             → not equals
 *   ?country=in(india,usa)      → IN
 *   ?country=not_in(india,usa)  → NOT IN
 *   ?price=between(100,500)     → BETWEEN
 *   ?deleted=null               → IS NULL
 *   ?deleted=not_null           → IS NOT NULL
 *   ?active=true|false|1|0      → boolean
 *   ?sort=-created_at,name      → sorting
 *   ?search=john                → full-text search
 */
final class RequestParser implements ParserContract
{
    /**
     * Regex to extract the operator from the query param key.
     * Supports: >=, <=, !=, >, <, ~, !~
     */
    private const KEY_OPERATOR_PATTERN = '/^(?P<field>[a-zA-Z0-9_.]+)(?P<op>>=|<=|!=|>|<|!~|~)?$/';

    /**
     * Map raw operator strings (from the URL) to canonical Operator enum values.
     */
    private const INLINE_OPERATOR_MAP = [
        '>'  => Operator::GreaterThan,
        '>=' => Operator::GreaterThanOrEqual,
        '<'  => Operator::LessThan,
        '<=' => Operator::LessThanOrEqual,
        '!=' => Operator::NotEquals,
        '~'  => Operator::Like,
        '!~' => Operator::NotLike,
    ];

    /**
     * Value-level function calls: in(...), not_in(...), between(...), not_between(...)
     */
    private const VALUE_FUNCTION_PATTERN = '/^(?P<func>not_in|not_between|between|in)\((?P<args>.+)\)$/i';

    /**
     * @param  list<string>  $ignoredFields  Fields that are reserved and must not be filtered (e.g. page, per_page)
     * @param  list<string>  $searchableFields  Default fields to search across when ?search= is used
     */
    public function __construct(
        private readonly array $ignoredFields = [],
        private readonly array $searchableFields = [],
        private readonly string $sortParam = 'sort',
        private readonly string $searchParam = 'search',
    ) {}

    public function parse(Request $request): FilterCollection
    {
        $collection = new FilterCollection();
        $collection = $this->parseSorts($request, $collection);
        $collection = $this->parseSearch($request, $collection);
        $collection = $this->parseFilters($request, $collection);

        return $collection;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function parseSorts(Request $request, FilterCollection $collection): FilterCollection
    {
        $rawSort = $request->query($this->sortParam);

        if (! is_string($rawSort) || $rawSort === '') {
            return $collection;
        }

        foreach (explode(',', $rawSort) as $part) {
            $part = trim($part);
            if ($part !== '') {
                $collection = $collection->withSort(SortInput::fromRaw($part));
            }
        }

        return $collection;
    }

    private function parseSearch(Request $request, FilterCollection $collection): FilterCollection
    {
        $term = $request->query($this->searchParam);

        if (! is_string($term) || $term === '') {
            return $collection;
        }

        return $collection->withSearch(new SearchInput(
            term: $this->sanitiseString($term),
            fields: $this->searchableFields,
        ));
    }

    private function parseFilters(Request $request, FilterCollection $collection): FilterCollection
    {
        /** @var array<string, mixed> $params */
        $params = $request->query();

        // Remove reserved params
        $reserved = array_merge(
            [$this->sortParam, $this->searchParam],
            $this->ignoredFields,
            ['page', 'per_page', 'limit', 'offset'],
        );

        foreach ($params as $rawKey => $rawValue) {
            // Skip reserved params
            if (in_array($rawKey, $reserved, true)) {
                continue;
            }

            // Skip nested arrays — not handled in Phase 1
            if (is_array($rawValue)) {
                continue;
            }

            $filter = $this->parseParam((string) $rawKey, (string) $rawValue);

            if ($filter !== null) {
                $collection = $collection->withFilter($filter);
            }
        }

        return $collection;
    }

    private function parseParam(string $rawKey, string $rawValue): ?FilterInput
    {
        // Extract field + optional inline operator from the key
        if (! preg_match(self::KEY_OPERATOR_PATTERN, $rawKey, $keyMatches)) {
            return null;
        }

        $field       = $keyMatches['field'];
        $inlineOp    = $keyMatches['op'] ?? '';
        $sanitised   = $this->sanitiseString($rawValue);

        // Inline operator takes precedence (e.g. ?age>=25)
        if ($inlineOp !== '') {
            $operator = self::INLINE_OPERATOR_MAP[$inlineOp] ?? null;

            if ($operator === null) {
                return null;
            }

            return new FilterInput($field, $operator->value, $sanitised);
        }

        // Value-level functions: in(...), between(...), null, not_null, true/false
        return $this->resolveValueOperator($field, $sanitised);
    }

    private function resolveValueOperator(string $field, string $value): FilterInput
    {
        // IS NULL / IS NOT NULL
        if (strtolower($value) === 'null') {
            return new FilterInput($field, Operator::IsNull->value, null);
        }

        if (strtolower($value) === 'not_null') {
            return new FilterInput($field, Operator::IsNotNull->value, null);
        }

        // Boolean
        if (in_array(strtolower($value), ['true', 'false', '1', '0'], true)) {
            return new FilterInput($field, Operator::Boolean->value, $value);
        }

        // Function-style values: in(...), not_in(...), between(...), not_between(...)
        if (preg_match(self::VALUE_FUNCTION_PATTERN, $value, $fnMatches)) {
            $func = strtolower($fnMatches['func']);
            $args = array_map(
                fn (string $a) => $this->sanitiseString(trim($a)),
                explode(',', $fnMatches['args'])
            );

            $operator = match ($func) {
                'in'          => Operator::In->value,
                'not_in'      => Operator::NotIn->value,
                'between'     => Operator::Between->value,
                'not_between' => Operator::NotBetween->value,
                default       => Operator::Equals->value,
            };

            return new FilterInput($field, $operator, $args);
        }

        // Default: equals
        return new FilterInput($field, Operator::Equals->value, $value);
    }

    /**
     * Strip characters that have no business in a filter value.
     * This is a defence-in-depth measure — Eloquent's parameter binding
     * already prevents SQL injection, but we keep values clean.
     */
    private function sanitiseString(string $value): string
    {
        // Remove null bytes and control characters
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value) ?? $value;
    }
}
