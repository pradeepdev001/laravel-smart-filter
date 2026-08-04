# Contributing to laravel-smart-filter

Thank you for considering a contribution. This guide will get you up and running quickly.

## Setup

```bash
git clone https://github.com/pradeepdev001/laravel-smart-filter.git
cd laravel-smart-filter
composer install
```

## Running Tests

```bash
composer test
```

With coverage:

```bash
composer test:coverage
```

## Code Style

This project uses Laravel Pint:

```bash
composer format
```

## Static Analysis

```bash
composer analyse
```

## Submitting a Pull Request

1. Fork the repository and create a feature branch from `develop`.
2. Write tests for your change. PRs without tests will not be merged.
3. Ensure `composer test`, `composer analyse`, and `composer format` all pass.
4. Add a `CHANGELOG.md` entry under `[Unreleased]`.
5. Open a PR against `develop`, not `main`.

## Adding a Custom Operator

Implement `YourVendor\SmartFilter\Contracts\OperatorContract`, then register it:

```php
SmartFilter::extend(new MyOperator());
```

or via config:

```php
// config/smart-filter.php
'operators' => [App\Filters\MyOperator::class],
```

## Versioning

This project follows [Semantic Versioning](https://semver.org). Breaking changes require a major version bump.
