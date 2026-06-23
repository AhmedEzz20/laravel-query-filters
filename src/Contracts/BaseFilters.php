<?php

namespace DevAstro\LaravelQueryFilters\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

abstract class BaseFilters
{
    /**
     * @var Request
     */
    protected Request $request;

    /**
     * @var Builder
     */
    protected Builder $builder;

    /**
     * Registered filters.
     *
     * @var array
     */
    protected array $filters = [];

    /**
     * BaseFilters constructor.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Apply filters to query.
     */
    public function apply(Builder $builder): Builder
    {
        // ✅ SORTING SYSTEM
        $this->applySorting();

        foreach ($this->getFilters() as $filter) {

            if (! $this->request->has($filter)) {
                continue;
            }

            $value = $this->request->query($filter) ?? $this->request[$filter];

            if (str_contains($filter, '_from')) {
                $column = str_replace('_from', '', $filter);
                $this->builder->whereDate($column, '>=', $value);
                continue;
            }

            if (str_contains($filter, '_to')) {
                $column = str_replace('_to', '', $filter);
                $this->builder->whereDate($column, '<=', $value);
                continue;
            }

            $methodName = Str::camel($filter);

            if (method_exists($this, $methodName)) {
                $this->$methodName($value);
            }
        }

        return $this->builder;
    }

    /**
     * Get registered filters.
     */
    public function getFilters(): array
    {
        return property_exists($this, 'filters')
            && is_array($this->filters)
            ? $this->filters
            : [];
    }

    protected function applySorting(): void
    {
        $orderBy = $this->request->get('order_by');
        $orderDir = strtolower($this->request->get('order_dir', 'asc'));

        if (! $orderBy) {
            return;
        }

        // whitelist direction
        if (! in_array($orderDir, ['asc', 'desc'])) {
            $orderDir = 'asc';
        }

        // optional: protect against invalid columns (recommended)
        if (! in_array($orderBy, $this->getSortableColumns())) {
            return;
        }

        $this->builder->orderBy($orderBy, $orderDir);
    }

    protected function getSortableColumns(): array
    {
        return property_exists($this, 'sortable')
            ? $this->sortable
            : [];
    }
}
