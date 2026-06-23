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

Install via Composer:

```bash
composer require dev-astro/laravel-query-filters
```

The package uses Laravel's auto-discovery, so no manual registration is needed.

---

## How It Works

The package works by mapping request query string keys to method names in your filter class. For every key in the request (e.g. `search_name`), it looks for a `camelCase` method with the same name (`searchName`) in your filter class and calls it automatically — passing the value as the argument.

If a key is present in the request but has no corresponding method, it is silently ignored.

---

## Features

- Simple filter classes per model
- Automatic request-based filtering (snake_case keys → camelCase methods)
- Date range support (`created_from`, `created_to`)
- Sorting support (`order_by`, `order_dir`)
- Whitelist-based filter security via `$filters` array
- Whitelist-based sortable columns via `$sortable` array
- Clean and reusable architecture
- Lightweight and easy to extend

---

## Usage

### 1. Add the `Filterable` Trait to Your Model

```php
use DevAstro\LaravelQueryFilters\Traits\Filterable;

class Brand extends Model
{
    use Filterable;

    // Point to your filter class
    protected $filter = BrandFilter::class;
}
```

---

### 2. Create a Filter Class

Place your filter classes in `app/Filters/`. Each filter class extends `BaseFilters`.

```php
use DevAstro\LaravelQueryFilters\Contracts\BaseFilters;

class BrandFilter extends BaseFilters
{
    /**
     * Whitelisted filter keys from the request.
     * Only keys listed here will be processed.
     */
    protected array $filters = [
        'search_name',
        'created_from',
        'created_to',
    ];

    /**
     * Columns allowed for sorting via order_by.
     * Requests with other column names will be ignored.
     */
    protected array $sortable = [
        'id',
        'name',
        'created_at',
    ];

    /**
     * Called when ?search_name=value is present in the request.
     * Method name is the camelCase version of the query key.
     */
    protected function searchName($value)
    {
        return $this->builder->where('name', 'like', "%{$value}%");
    }
}
```

> **Naming convention:** Request key `search_name` → method `searchName()`. Always use `snake_case` in `$filters` and `camelCase` for the method name.

---

### 3. Use in Controller or Repository

```php
// Apply all matching filters from the current request
Brand::filter()->paginate();

// With additional constraints
Brand::filter()->where('is_active', true)->paginate();

// Without pagination
Brand::filter()->get();
```

The `filter()` scope automatically resolves the registered filter class from the model's `$filter` property and applies all matching request parameters.

---

## Built-in Filters

### Date Range

The `created_from` and `created_to` keys are built into `BaseFilters` — you don't need to define methods for them, just add them to your `$filters` array:

```php
protected array $filters = [
    'created_from',
    'created_to',
];
```

Example request:
```
GET /api/brands?created_from=2024-01-01&created_to=2024-12-31
```

---

### Sorting

Sorting is built in. Add `order_by` and `order_dir` to your `$filters` array, and list allowed columns in `$sortable`:

```php
protected array $filters = [
    'order_by',
    'order_dir',
];

protected array $sortable = [
    'id',
    'name',
    'created_at',
];
```

Example requests:
```
GET /api/brands?order_by=name&order_dir=asc
GET /api/brands?order_by=created_at&order_dir=desc
```

> If `order_by` is not in `$sortable`, the sort is ignored. Default direction is `asc`.

---

## Full Example

**Request:**
```
GET /api/brands?search_name=nike&created_from=2024-01-01&order_by=name&order_dir=desc
```

**Filter class:**
```php
use DevAstro\LaravelQueryFilters\Contracts\BaseFilters;

class BrandFilter extends BaseFilters
{
    protected array $filters = [
        'search_name',
        'created_from',
        'created_to',
        'order_by',
        'order_dir',
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
  AND created_at >= '2024-01-01'
ORDER BY name DESC
```

---

## Writing Custom Filters

Any method in your filter class that matches a whitelisted key (after camelCase conversion) will be called with the request value:

```php
protected function status($value)
{
    return $this->builder->where('status', $value);
}

protected function categoryId($value)
{
    return $this->builder->where('category_id', $value);
}

protected function minFollowers($value)
{
    return $this->builder->where('followers_count', '>=', $value);
}

protected function hasProducts($value)
{
    return $this->builder->has('products');
}
```

> You always have access to `$this->builder` which is the Eloquent query builder instance for the model.

---

## Package Structure

```
src/
├── Contracts/
│   └── BaseFilters.php          # Base class for all filter classes
├── Traits/
│   └── Filterable.php           # Trait added to Eloquent models
└── LaravelQueryFiltersServiceProvider.php

stubs/
└── Filter.stub                  # Stub used for filter generation
```

---

## Quick Reference

| Request Key | Filter Method | Example URL |
|---|---|---|
| `search_name` | `searchName($value)` | `?search_name=nike` |
| `created_from` | Built-in | `?created_from=2024-01-01` |
| `created_to` | Built-in | `?created_to=2024-12-31` |
| `order_by` | Built-in | `?order_by=name` |
| `order_dir` | Built-in | `?order_dir=desc` |
| `category_id` | `categoryId($value)` | `?category_id=5` |
| `min_followers` | `minFollowers($value)` | `?min_followers=1000` |

---

## License

MIT
