<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter\Parser;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Pradeepdev\SmartFilter\Collections\FilterCollection;
use Pradeepdev\SmartFilter\Contracts\ParserContract;
use Pradeepdev\SmartFilter\DTOs\FilterInput;
use Pradeepdev\SmartFilter\DTOs\RelationFilterInput;
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
 *
 * Phase 2 — Relationship filtering (dot notation):
 *   ?posts.status=published     → whereHas('posts', fn($q) => $q->where('status','published'))
 *   ?posts.title~laravel        → whereHas('posts', fn($q) => $q->where('title','LIKE','%laravel%'))
 *   ?company.address.city=NYC   → nested whereHas chaining
 *   ?posts=has                  → has('posts')
 *   ?posts=doesntHave           → doesntHave('posts')
 *   ?posts=orHas                → orHas('posts')
 */
final class RequestParser implements ParserContract
{
    /**
     * Regex to extract the operator from the query param key.
     * Supports: >=, <=, !=, >, <, ~, !~
     * Also supports dot-notation fields like posts.status or company.address.city
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

            $rawKeyStr   = (string) $rawKey;
            $rawValueStr = (string) $rawValue;

            // Detect dot-notation: route to relation filter parser
            // A key is relational if it contains a dot anywhere in it,
            // OR if its value is an existence keyword (has, doesntHave, orHas, doesntHave)
            if ($this->isRelationKey($rawKeyStr, $rawValueStr)) {
                $filter = $this->parseRelationParam($rawKeyStr, $rawValueStr);

                if ($filter !== null) {
                    $collection = $collection->withRelationFilter($filter);
                }

                continue;
            }

            $filter = $this->parseParam($rawKeyStr, $rawValueStr);

            if ($filter !== null) {
                $collection = $collection->withFilter($filter);
            }
        }

        return $collection;
    }

    /**
     * Determine whether this param should be treated as a relation filter.
     * A param is relational when:
     *   - The (stripped) key contains a dot  (e.g. posts.status, company.address.city)
     *   - The value is a bare existence keyword with no dot (e.g. ?posts=has)
     */
    private function isRelationKey(string $rawKey, string $rawValue): bool
    {
        // Strip any inline operator suffix to get the bare key
        $bareKey = rtrim($rawKey, '>=<!~');

        if (str_contains($bareKey, '.')) {
            return true;
        }

        // Existence checks: ?posts=has, ?posts=doesntHave, ?posts=orHas
        if (in_array(strtolower($rawValue), ['has', 'doesnt_have', 'doesnthave', 'orhas', 'or_has'], true)) {
            return true;
        }

        return false;
    }

    /**
     * Parse a relation filter param into a RelationFilterInput.
     *
     * Examples:
     *   posts.status=published   → ['posts'], 'status', 'eq', 'published'
     *   posts.title~laravel      → ['posts'], 'title', 'like', 'laravel'
     *   company.address.city=NYC → ['company', 'address'], 'city', 'eq', 'NYC'
     *   posts=has                → ['posts'], null, 'has', null
     *   posts=doesntHave         → ['posts'], null, 'doesnt_have', null
     */
    private function parseRelationParam(string $rawKey, string $rawValue): ?RelationFilterInput
    {
        // Check for existence operators first (key has no dot)
        $lowerValue = strtolower($rawValue);

        if (in_array($lowerValue, ['has', 'doesnt_have', 'doesnthave'], true)) {
            $op = ($lowerValue === 'has') ? 'has' : 'doesnt_have';

            return new RelationFilterInput(
                relation: [$this->sanitiseString($rawKey)],
                field: null,
                operator: $op,
                value: null,
            );
        }

        if (in_array($lowerValue, ['orhas', 'or_has'], true)) {
            return new RelationFilterInput(
                relation: [$this->sanitiseString($rawKey)],
                field: null,
                operator: 'or_has',
                value: null,
            );
        }

        // Dot-notation: extract inline operator from the key suffix
        if (! preg_match(self::KEY_OPERATOR_PATTERN, $rawKey, $keyMatches)) {
            return null;
        }

        $fullField = $keyMatches['field']; // e.g. "posts.status" or "company.address.city"
        $inlineOp  = $keyMatches['op'] ?? '';

        $segments = explode('.', $fullField);

        if (count($segments) < 2) {
            return null;
        }

        // Last segment = leaf field, everything before = relation chain
        $field    = array_pop($segments);
        $relation = $segments;

        $sanitisedValue = $this->sanitiseString($rawValue);

        // Inline operator (e.g. posts.price>=100)
        if ($inlineOp !== '') {
            $operator = self::INLINE_OPERATOR_MAP[$inlineOp] ?? null;

            if ($operator === null) {
                return null;
            }

            return new RelationFilterInput(
                relation: $relation,
                field: $field,
                operator: $operator->value,
                value: $sanitisedValue,
            );
        }

        // Value-level operator (same logic as flat filters)
        $flatInput = $this->resolveValueOperator($field, $sanitisedValue);

        return new RelationFilterInput(
            relation: $relation,
            field: $flatInput->field,
            operator: $flatInput->operator,
            value: $flatInput->value,
        );
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
