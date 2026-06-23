# Laravel Query Filters

A simple and powerful query filtering system for Laravel.

This package helps you build clean, reusable, and scalable query filters for your Eloquent models with support for:
- Dynamic filters
- Date range filtering
- Sorting system
- Clean architecture

---

# Installation

Install via Composer:

```bash
composer require astro/laravel-query-filters
```

---

# Features

- Simple filter classes per model
- Automatic request-based filtering
- Date range support (`created_from`, `created_to`)
- Sorting support (`order_by`, `order_dir`)
- Clean and reusable architecture
- Lightweight and easy to extend

---

# Usage

## 1. Add Filterable Trait to Model

```php
use Astro\\LaravelQueryFilters\\Traits\\Filterable;

class Brand extends Model
{
    use Filterable;

    protected $filter = BrandFilter::class;
}
```

---

## 2. Create Filter Class

```php
use Astro\\LaravelQueryFilters\\Contracts\\BaseFilters;

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
        return $this->builder
            ->where('name', 'like', "%{$value}%");
    }
}
```

---

## 3. Usage in Controller / Repository

```php
Brand::filter()->paginate();
```

---

# Query Examples

## Search

/api/brands?search_name=nike

## Date Range

/api/brands?created_from=2024-01-01&created_to=2024-12-31

## Sorting

/api/brands?order_by=name&order_dir=asc

/api/brands?order_by=created_at&order_dir=desc

---

# License

MIT
