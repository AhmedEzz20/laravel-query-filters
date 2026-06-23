# Laravel Query Filters

A simple and powerful query filtering system for Laravel.

This package helps you build clean, reusable, and scalable query filters for your Eloquent models with support for:
- Dynamic filters
- Date range filtering
- Sorting system
- Clean architecture

---

## Requirements

- PHP `^8.1`
- Laravel `10.x | 11.x | 12.x`

---

## Installation

```bash
composer require dev-astro/laravel-query-filters
```

The package uses Laravel's auto-discovery — no manual registration needed.

---

## How It Works

The package works by mapping request query string keys to method names in your filter class.

For every key in the `$filters` array, it checks if the request contains it, then calls the matching `camelCase` method in your filter class with the value.

```
Request key  →  Method name
search_name  →  searchName($value)
category_id  →  categoryId($value)
```

Keys ending in `_from` and `_to` are handled automatically as date range filters (no method needed).

---

## Features

- Artisan command to generate filter classes (`make:filter`)
- Automatic `Filterable` trait registration via auto-discovery
- Dynamic filters via `$filters` whitelist
- Date range filtering (`_from` / `_to` convention)
- Built-in sorting (`order_by` / `order_dir`) with `$sortable` whitelist
- `perPage` support via query string
- Lightweight — no config file needed

---

## Step-by-step Usage

### 1. Add the `Filterable` Trait to Your Model

```php
use DevAstro\LaravelQueryFilters\Traits\Filterable;

class Brand extends Model
{
    use Filterable;

    protected $filter = BrandFilter::class;
}
```

The trait adds:
- A `filter()` Eloquent scope that auto-resolves your filter class
- Support for `?perPage=20` in the request to override pagination size

---

### 2. Generate a Filter Class

```bash
php artisan make:filter BrandFilter
```

This creates `app/Http/Filters/BrandFilter.php`:

```php
namespace App\Http\Filters;

use DevAstro\LaravelQueryFilters\Contracts\BaseFilters;

class BrandFilter extends BaseFilters
{
    protected array $filters = [
        //
    ];

    protected function example($value)
    {
        return $this->builder;
    }
}
```

---

### 3. Define Your Filters

Add filter keys to the `$filters` array and write a matching `camelCase` method for each:

```php
class BrandFilter extends BaseFilters
{
    protected array $filters = [
        'search_name',
        'status',
        'created_from',   // handled automatically — no method needed
        'created_to',     // handled automatically — no method needed
    ];

    protected array $sortable = [
        'id',
        'name',
        'created_at',
    ];

    protected function searchName($value)
    {
        return $this->builder->where('name', 'like', "%{$value}%");
    }

    protected function status($value)
    {
        return $this->builder->where('status', $value);
    }
}
```

---

### 4. Use in Your Controller

```php
public function index()
{
    return BrandResource::collection(
        Brand::filter()->paginate()
    );
}
```

That's it. The `filter()` scope automatically picks up the current request and applies all matching filters.

---

## Built-in Features

### Date Range (`_from` / `_to`)

Any key ending in `_from` or `_to` is automatically handled as a `whereDate` filter — no method needed:

```php
protected array $filters = [
    'created_from',  // WHERE DATE(created_at) >= ?
    'created_to',    // WHERE DATE(created_at) <= ?
];
```

> The column name is derived by stripping the suffix: `created_from` → `created` column... so name your keys as `{column}_from` / `{column}_to`. For example, `published_from` filters on the `published` column.

Example request:
```
GET /api/brands?created_from=2024-01-01&created_to=2024-12-31
```

---

### Sorting (`order_by` / `order_dir`)

Sorting is handled automatically — you don't need to add `order_by` or `order_dir` to your `$filters` array. Just define which columns are allowed in `$sortable`:

```php
protected array $sortable = [
    'id',
    'name',
    'created_at',
];
```

- `order_dir` defaults to `asc` if not provided or invalid
- If `order_by` column is not in `$sortable`, the sort is silently ignored (security)

Example requests:
```
GET /api/brands?order_by=name&order_dir=asc
GET /api/brands?order_by=created_at&order_dir=desc
```

---

### Pagination (`perPage`)

The `Filterable` trait overrides `getPerPage()`, so you can control page size from the request:

```
GET /api/brands?perPage=20
GET /api/brands?perPage=50&page=2
```

Falls back to the model's default (`15`) if not provided.

---

## Full Example

**Request:**
```
GET /api/brands?search_name=nike&created_from=2024-01-01&order_by=name&order_dir=desc&perPage=20
```

**Filter class:**
```php
namespace App\Http\Filters;

use DevAstro\LaravelQueryFilters\Contracts\BaseFilters;

class BrandFilter extends BaseFilters
{
    protected array $filters = [
        'search_name',
        'created_from',
        'created_to',
    ];

    protected array $sortable = [
        'id',
        'name',
        'created_at',
    ];

    protected function searchName($value)
    {
        return $this->builder->where('name', 'like', "%{$value}%");
    }
}
```

**Model:**
```php
use DevAstro\LaravelQueryFilters\Traits\Filterable;

class Brand extends Model
{
    use Filterable;

    protected $filter = BrandFilter::class;
}
```

**Controller:**
```php
public function index()
{
    return BrandResource::collection(
        Brand::filter()->paginate()
    );
}
```

**Equivalent SQL:**
```sql
SELECT * FROM brands
WHERE name LIKE '%nike%'
  AND DATE(created_at) >= '2024-01-01'
ORDER BY name DESC
LIMIT 20
```

---

## Writing Custom Filters

Every filter method receives the request value and has access to `$this->builder`:

```php
// Exact match
protected function status($value)
{
    return $this->builder->where('status', $value);
}

// Relationship filter
protected function categoryId($value)
{
    return $this->builder->where('category_id', $value);
}

// Range filter (manual)
protected function minFollowers($value)
{
    return $this->builder->where('followers_count', '>=', $value);
}

// Existence check (boolean flag)
protected function hasProducts($value)
{
    return $this->builder->has('products');
}

// whereIn
protected function statuses($value)
{
    return $this->builder->whereIn('status', explode(',', $value));
}
```

---

## Quick Reference

| Request Parameter | Behavior | Needs method? |
|---|---|---|
| `?search_name=x` | calls `searchName($value)` | ✅ Yes |
| `?created_from=date` | `whereDate(column, '>=', date)` | ❌ No |
| `?created_to=date` | `whereDate(column, '<=', date)` | ❌ No |
| `?order_by=col&order_dir=asc` | `orderBy(col, dir)` — only if in `$sortable` | ❌ No |
| `?perPage=20` | overrides pagination size | ❌ No |

---

## Package Structure

```
src/
├── Commands/
│   └── MakeFilterCommand.php    # php artisan make:filter
├── Contracts/
│   └── BaseFilters.php          # Base class for all filter classes
├── Traits/
│   └── Filterable.php           # Trait added to Eloquent models
└── LaravelQueryFiltersServiceProvider.php

stubs/
└── filter.stub                  # Template used by make:filter
```

---

## License

MIT
