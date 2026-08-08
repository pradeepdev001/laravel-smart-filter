# laravel-smart-filter

[![Tests](https://github.com/pradeepdev001/laravel-smart-filter/actions/workflows/tests.yml/badge.svg)](https://github.com/pradeepdev001/laravel-smart-filter/actions)
[![PHPStan](https://github.com/pradeepdev001/laravel-smart-filter/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/pradeepdev001/laravel-smart-filter/actions)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/pradeepdev001/laravel-smart-filter.svg)](https://packagist.org/packages/pradeepdev001/laravel-smart-filter)
[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue)](https://packagist.org/packages/pradeepdev001/laravel-smart-filter)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

A powerful, elegant, and extensible Eloquent filtering package for Laravel 8, 9, 10, 11 & 12.

Stop writing repetitive `when()` chains. Let your URL do the talking.

```php
// Before
User::query()
    ->when($request->status, fn ($q, $v) => $q->where('status', $v))
    ->when($request->input('age>'), fn ($q, $v) => $q->where('age', '>', $v))
    ->when($request->country, fn ($q, $v) => $q->whereIn('country', explode(',', $v)))
    ->whereHas('posts', fn ($q) => $q->where('status', $request->posts_status))
    ->orderBy('created_at', 'desc')
    ->paginate();

// After
User::smartFilter()->paginate();
```

---

## Requirements

- PHP 8.1+
- Laravel 8, 9, 10, 11, or 12

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
use Pradeepdev\SmartFilter\Traits\Filterable;

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

Or chain on an existing query:

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

## Relationship Filtering

SmartFilter uses **dot notation** to filter across Eloquent relationships. No extra configuration needed — just use the relationship method name followed by the field you want to filter on.

### Basic whereHas

Filter users who have at least one published post:

```
GET /users?posts.status=published
```

All Phase 1 operators work inside relationship filters:

```
GET /users?posts.title~laravel          → posts with title containing "laravel"
GET /users?posts.status!=draft          → posts where status is not draft
GET /users?posts.status=in(published,archived)
GET /users?posts.views>=1000
```

### Nested Relationships

Chain as many levels deep as you need:

```
GET /users?company.name=Acme Corp
GET /users?company.city~New
GET /users?company.address.city=London
```

Each dot segment is a relationship method name. The last segment is the field on the related model's table.

### Many-to-Many

Works with `BelongsToMany` out of the box:

```
GET /users?roles.name=admin
GET /users?roles.name=in(admin,editor)
```

### Existence Checks

Check whether a relationship exists or not, without filtering on a specific field:

```
GET /users?posts=has           → users who have at least one post
GET /users?posts=doesntHave    → users who have no posts
GET /users?posts=orHas         → OR has at least one post
```

### Combining with Flat Filters

Relationship filters and flat filters compose naturally with AND logic:

```
GET /users?status=active&posts.status=published
GET /users?country=india&roles.name=admin&sort=-created_at
```

### Model Setup for Relationships

Define your Eloquent relationships as normal. No special configuration needed on the related model:

```php
class User extends Model
{
    use Filterable;

    protected array $filterable = ['name', 'email', 'status'];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
```

> **Note:** The parent model's `$filterable` whitelist applies only to the parent's own columns. Fields inside a relationship subquery are validated independently, so `posts.title` will work even if `title` is not in the User model's `$filterable`.

---

## Configuration

After publishing (`vendor:publish --tag=smart-filter-config`), `config/smart-filter.php` gives you global control:

```php
return [
    'allowed_fields'    => [],               // Global whitelist (empty = all allowed)
    'ignored_fields'    => ['password'],     // Always blocked
    'aliases'           => [],               // ['city' => 'address_city']
    'searchable_fields' => [],               // Default ?search= fields
    'sort_param'        => 'sort',           // Query param for sorting
    'search_param'      => 'search',         // Query param for search
    'strict'            => false,            // Throw on unknown fields
    'operators'         => [],               // Custom operator classes
    'debug'             => false,            // Log parsed filters + SQL
];
```

---

## Model-Level Configuration

Model properties always take precedence over global config:

| Property          | Type                    | Description                                    |
|-------------------|-------------------------|------------------------------------------------|
| `$filterable`     | `list<string>`          | Whitelisted fields. Empty = all allowed.       |
| `$filterIgnore`   | `list<string>`          | Always-blocked fields.                         |
| `$filterAliases`  | `array<string, string>` | Request param → real column name mapping.      |
| `$searchable`     | `list<string>`          | Fields used by `?search=`.                     |
| `$filterStrict`   | `bool`                  | Throw exceptions instead of silently skipping. |

---

## Custom Operators

Implement `OperatorContract` and register it in a service provider:

```php
use Pradeepdev\SmartFilter\Facades\SmartFilter;
use Pradeepdev\SmartFilter\Contracts\OperatorContract;
use Pradeepdev\SmartFilter\DTOs\FilterInput;
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

Use it in the URL:

```
GET /users?name=starts_with(Jo)
```

Or register via config:

```php
// config/smart-filter.php
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

Pass a custom `Request` directly to `smartFilter()` to keep tests fast and HTTP-free:

```php
$request = Request::create('/users', 'GET', [
    'status'       => 'active',
    'posts.status' => 'published',
    'sort'         => '-created_at',
]);

$results = User::smartFilter($request)->get();
```

---

## Architecture

```
src/
├── Contracts/      — FilterContract, OperatorContract, ParserContract, OperatorRegistryContract
├── DTOs/           — FilterInput, RelationFilterInput, SortInput, SearchInput (all readonly)
├── Enums/          — Operator (canonical names), SortDirection
├── Collections/    — FilterCollection (immutable typed container)
├── Parser/         — RequestParser (HTTP → FilterCollection, with dot-notation routing)
├── Operators/      — One class per operator (Equals, Like, In, Between, …)
├── Builders/       — FilterBuilder, RelationFilterApplier
├── Support/        — OperatorRegistry, FieldGuard
├── Traits/         — Filterable (adds scopeSmartFilter to any model)
├── Facades/        — SmartFilter
├── Exceptions/     — Typed, descriptive exceptions
└── SmartFilterServiceProvider.php
```

**Data flow:**

```
HTTP Request
     ↓
RequestParser  ──→  FilterCollection
                      ├── filters[]          (flat WHERE conditions)
                      ├── relationFilters[]  (dot-notation whereHas chains)
                      ├── sorts[]
                      └── search
     ↓
FieldGuard     ──→  alias resolution, allow/deny on flat fields
     ↓
FilterBuilder
  ├── flat filters    ──→  OperatorRegistry  ──→  Builder::where(...)
  ├── relation filters ──→  RelationFilterApplier  ──→  Builder::whereHas(...)
  ├── sorts           ──→  Builder::orderBy(...)
  └── search          ──→  Builder::where(orWhere...)
     ↓
Eloquent Builder (fully composed, ready for ->get() / ->paginate())
```

---

## Roadmap

| Phase | Status       | Feature                                        |
|-------|--------------|------------------------------------------------|
| 1     | ✅ Complete   | Core operators, sorting, search                |
| 2     | ✅ Complete   | Relationship filtering (whereHas, has, nested) |
| 3     | 🔄 Planned   | Date filters (today, last_week, this_month…)   |
| 4     | 🔄 Planned   | JSON column filtering                          |
| 5     | ✅ Complete   | Custom operator registration                   |
| 6     | ✅ Complete   | Full config publishing                         |
| 7     | 🔄 Planned   | Performance optimisation & caching             |
| 8     | 🔄 Planned   | Macros, IDE helpers, PHPStan types             |
| 9     | ✅ Partial    | Pest test suite (105 tests, 210 assertions)    |
| 10    | ✅ Complete   | README & documentation                         |

---

## FAQ

**Q: Does this prevent SQL injection?**  
A: Yes. All filter values are passed via PDO bound parameters through Eloquent. Field names are validated against an allow-list before interpolation. Input is additionally sanitised at parse time (null bytes and control characters stripped).

**Q: Can I use this without the trait?**  
A: Yes. Resolve `FilterBuilder` from the container and call `apply($builder, $collection)` directly.

**Q: What happens with unknown filter params?**  
A: By default they are silently skipped. Set `$filterStrict = true` on the model (or `'strict' => true` in config) to throw `InvalidFilterFieldException` instead.

**Q: Does the parent model's `$filterable` block relationship fields?**  
A: No. `$filterable` on the parent model only applies to the parent's own columns. Fields inside a relationship subquery (`posts.title`, `company.city`) use a permissive guard so they're never accidentally blocked by the parent's whitelist.

**Q: Which relationship types are supported?**  
A: `HasMany`, `BelongsTo`, `BelongsToMany`, and `HasOne`. Any Eloquent relationship that supports `whereHas` works.

**Q: Can I nest relationships more than one level?**  
A: Yes, unlimited depth. `?company.address.city=London` works, as does `?org.department.team.lead_name=Alice`.

---

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## Security

See [SECURITY.md](SECURITY.md). Please do not open public issues for vulnerabilities.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

MIT — see [LICENSE](LICENSE).
