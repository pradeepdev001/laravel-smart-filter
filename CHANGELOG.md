# Changelog

All notable changes to `laravel-smart-filter` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed
- Expanded package support to Laravel 8, 9, 10, 11, and 12.
- Widened the CI matrix to exercise framework-specific Laravel, Testbench, and Pest combinations.
- Fixed PHPStan configuration so `composer analyse` runs on supported PHPStan versions.

## [1.1.0] — 2024-01-02

### Added
- Phase 2 — Relationship filtering
- Dot-notation URL syntax: `?posts.status=published`, `?company.city~New`
- All Phase 1 operators usable inside relationship subqueries
- Nested relationship chaining: `?company.address.city=London` (unlimited depth)
- Existence checks: `?posts=has`, `?posts=doesntHave`, `?posts=orHas`
- `HasMany`, `BelongsTo`, `BelongsToMany`, `HasOne` all supported
- `RelationFilterInput` immutable DTO with recursive `withoutRootRelation()`
- `RelationFilterApplier` with recursive `whereHas` chaining
- `FilterCollection::withRelationFilter()` and `::relationFilters()`
- `RequestParser` auto-routes dot-notation params to relation bucket
- 24 new tests — 105 total, 210 assertions

## [1.0.0] — 2024-01-01

### Added
- Initial release — Phase 1 MVP
- Operators: equals, not-equals, `>`, `>=`, `<`, `<=`, LIKE, NOT LIKE, IN, NOT IN, BETWEEN, NOT BETWEEN, IS NULL, IS NOT NULL, boolean
- Sorting via `?sort=-created_at,name`
- Full-text search via `?search=term` across configurable fields
- `Filterable` trait for Eloquent models
- Allowed-fields whitelist (`$filterable`)
- Ignored fields (`$filterIgnore`)
- Custom field aliases (`$filterAliases`)
- Strict mode (`$filterStrict`)
- `SmartFilter` facade with `extend()` for custom operators
- Publishable config at `config/smart-filter.php`
- Laravel auto-discovery for the service provider
- Pest test suite (60+ tests)
- GitHub Actions CI for PHP 8.3/8.4 × Laravel 11/12
- PHPStan static analysis workflow
