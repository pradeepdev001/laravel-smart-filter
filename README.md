# laravel-smart-filter

[![Tests](https://github.com/pradeepdev001/laravel-smart-filter/actions/workflows/tests.yml/badge.svg)](https://github.com/pradeepdev001/laravel-smart-filter/actions)
[![PHPStan](https://github.com/pradeepdev001/laravel-smart-filter/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/pradeepdev001/laravel-smart-filter/actions)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/pradeepdev001/laravel-smart-filter.svg)](https://packagist.org/packages/pradeepdev001/laravel-smart-filter)
[![PHP Version](https://img.shields.io/badge/php-%5E8.3-blue)](https://packagist.org/packages/pradeepdev001/laravel-smart-filter)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

A powerful, elegant, and extensible Eloquent filtering package for Laravel 11 & 12.

Stop writing repetitive `when()` chains. Let your URL do the talking.

```php
// Before
User::query()
    ->when($request->status, fn ($q, $v) => $q->where('status', $v))
    ->when($request->input('age>'), fn ($q, $v) => $q->where('age', '>', $v))
    ->when($request->country, fn ($q, $v) => $q->whereIn('country', explode(',', $v)))
    ->orderBy('created_at', 'desc')
    ->paginate();

// After
User::smartFilter()->paginate();
```

---

## Requirements

- PHP 8.3+
- Laravel 11 or 12

---

## Installation

```bash
composer require pradeepdev001/laravel-smart-filter
```

The service provider and facade are registered automatically via Laravel's package discovery.

**Publish the config (optional):**

```bash
php artisan vendor:publish --tag=smart-filter-config
```

---

## Quick Start

Add the `Filterable` trait to any Eloquent model:

```php
use YourVendor\SmartFilter\Traits\Filterable;

class User extends Model
{
    use Filterable;

    // Whitelist filterable fields (empty = all allowed)
    protected array $filterable = ['name', 'email', 'status', 'age', 'country'];

    // Fields searched by ?search=
    protected array $searchable = ['name', 'email'];

    // Protect sensitive columns
    protected array $filterIgnore = ['password', 'remember_token'];

    // Map request params to real columns
    protected array $filterAliases = ['city' => 'address_city'];
}
```

Then in your controller:

```php
public function index(): JsonResponse
{
    return User::smartFilter()->paginate();
}
```

Or on an existing query:

```php
User::where('tenant_id', auth()->id())->smartFilter()->paginate();
```

---

## Filter Syntax

### Equals

```
GET /users?status=active
GET /users?email=alice@example.com
```

### Not Equals

```
GET /users?status!=inactive
```

### Comparisons

```
GET /users?age>25
GET /users?price>=100
GET /users?age<40
GET /users?price<=500
```

### LIKE (contains)

```
GET /users?name~john        → WHERE name LIKE '%john%'
GET /users?name!~john       → WHERE name NOT LIKE '%john%'
```

### IN / NOT IN

```
GET /users?country=in(india,usa,uk)
GET /users?country=not_in(banned_country)
```

### BETWEEN / NOT BETWEEN

```
GET /users?price=between(100,500)
GET /users?price=not_between(0,99)
```

### NULL checks

```
GET /users?deleted_at=null       → WHERE deleted_at IS NULL
GET /users?deleted_at=not_null   → WHERE deleted_at IS NOT NULL
```

### Boolean

```
GET /users?is_active=true
GET /users?is_active=false
GET /users?is_active=1
GET /users?is_active=0
```

### Sorting

Prefix with `-` for descending. Comma-separate for multiple columns.

```
GET /users?sort=name              → ORDER BY name ASC
GET /users?sort=-created_at       → ORDER BY created_at DESC
GET /users?sort=-created_at,name  → ORDER BY created_at DESC, name ASC
```

### Full-text Search

```
GET /users?search=john    → WHERE (name LIKE '%john%' OR email LIKE '%john%')
```

The fields searched are defined by `$searchable` on the model or `searchable_fields` in config.

### Combining Filters

All filters are combined with `AND` logic. Mix freely:

```
GET /users?status=active&age>=25&country=in(india,usa)&sort=-created_at
```

---

## Configuration

After publishing (`vendor:publish --tag=smart-filter-config`), `config/smart-filter.php` gives you global control:

```php
return [
    'allowed_fields'   => [],               // Global whitelist (empty = all allowed)
    'ignored_fields'   => ['password'],     // Always blocked
    'aliases'          => [],               // ['city' => 'address_city']
    'searchable_fields'=> [],               // Default ?search= fields
    'sort_param'       => 'sort',           // Query param for sorting
    'search_param'     => 'search',         // Query param for search
    'strict'           => false,            // Throw on unknown fields
    'operators'        => [],               // Custom operator classes
    'debug'            => false,            // Log parsed filters + SQL
];
```

---

## Model-Level Configuration

Model properties always take precedence over global config:

| Property           | Type                    | Description                                    |
|--------------------|-------------------------|------------------------------------------------|
| `$filterable`      | `list<string>`          | Whitelisted fields. Empty = all allowed.       |
| `$filterIgnore`    | `list<string>`          | Always-blocked fields.                         |
| `$filterAliases`   | `array<string, string>` | Request param → real column name mapping.      |
| `$searchable`      | `list<string>`          | Fields used by `?search=`.                     |
| `$filterStrict`    | `bool`                  | Throw exceptions instead of silently skipping. |

---

## Custom Operators

Register a custom operator in a service provider:

```php
use YourVendor\SmartFilter\Facades\SmartFilter;
use YourVendor\SmartFilter\Contracts\OperatorContract;
use YourVendor\SmartFilter\DTOs\FilterInput;
use Illuminate\Database\Eloquent\Builder;

class StartsWithOperator implements OperatorContract
{
    public function apply(Builder $builder, FilterInput $input): Builder
    {
        return $builder->where($input->field, 'LIKE', $input->value . '%');
    }

    public function handles(): array
    {
        return ['starts_with'];
    }
}

// In AppServiceProvider::boot()
SmartFilter::extend(new StartsWithOperator());
```

Now use it in the URL:

```
GET /users?name=starts_with(Jo)
```

You can also register via config:

```php
'operators' => [App\Filters\StartsWithOperator::class],
```

---

## Pagination

SmartFilter is fully compatible with all Eloquent pagination methods:

```php
User::smartFilter()->paginate(15);
User::smartFilter()->simplePaginate(15);
User::smartFilter()->cursorPaginate(15);
```

---

## Testing Your Controllers

Pass a custom `Request` directly to `smartFilter()`:

```php
$request = Request::create('/users', 'GET', ['status' => 'active', 'sort' => '-name']);
$results = User::smartFilter($request)->get();
```

---

## Architecture

```
src/
├── Contracts/           — Interfaces: FilterContract, OperatorContract, ParserContract, OperatorRegistryContract
├── DTOs/                — Immutable value objects: FilterInput, SortInput, SearchInput
├── Enums/               — Operator (canonical names), SortDirection
├── Collections/         — FilterCollection (typed container)
├── Parser/              — RequestParser (HTTP → FilterCollection)
├── Operators/           — One class per operator (Equals, Like, In, Between, …)
├── Support/             — OperatorRegistry, FieldGuard
├── Builders/            — FilterBuilder (applies FilterCollection to Eloquent Builder)
├── Traits/              — Filterable (adds scopeSmartFilter to models)
├── Facades/             — SmartFilter facade
├── Exceptions/          — Typed, descriptive exceptions
└── SmartFilterServiceProvider.php
```

**Data flow:**

```
HTTP Request
     ↓
RequestParser  →  FilterCollection (DTOs)
     ↓
FieldGuard     →  alias resolution, allow/deny checks
     ↓
FilterBuilder  →  applies each FilterInput via OperatorRegistry
     ↓
Eloquent Builder (with all conditions applied)
```

---

## Roadmap

| Phase | Status      | Feature                                 |
|-------|-------------|-----------------------------------------|
| 1     | ✅ Complete  | Core operators, sorting, search         |
| 2     | 🔄 Planned  | Relationship filtering (whereHas, etc.) |
| 3     | 🔄 Planned  | Date filters (today, last_week, etc.)   |
| 4     | 🔄 Planned  | JSON column filtering                   |
| 5     | ✅ Complete  | Custom operator registration            |
| 6     | ✅ Complete  | Full config publishing                  |
| 7     | 🔄 Planned  | Performance optimisation & caching      |
| 8     | 🔄 Planned  | Macros, IDE helpers, PHPStan types      |
| 9     | ✅ Partial   | Pest test suite                         |
| 10    | ✅ Complete  | README & documentation                  |

---

## FAQ

**Q: Does this prevent SQL injection?**  
A: Yes. All filter values are passed via PDO bound parameters through Eloquent. Field names are validated against an allow-list before interpolation. Input is additionally sanitised at parse time.

**Q: Can I use this without the trait?**  
A: Yes. Resolve `FilterBuilder` from the container and call `apply($builder, $collection)` directly.

**Q: What happens with unknown filter params?**  
A: By default they are silently skipped. Enable `$filterStrict = true` on the model (or `'strict' => true` in config) to throw `InvalidFilterFieldException` instead.

**Q: Can I filter on relationships?**  
A: Phase 2 (coming soon) adds `whereHas`, `orWhereHas`, and nested relationship support.

---

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## Security

See [SECURITY.md](SECURITY.md). Please do not open public issues for vulnerabilities.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

MIT — see [LICENSE](LICENSE).
