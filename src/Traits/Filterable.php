<?php

namespace Astro\LaravelQueryFilters\Traits;

use Astro\LaravelQueryFilters\Contracts\BaseFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;

/**
 * @method static Builder|$this filter(BaseFilters $filters = null)
 */
trait Filterable
{
    /**
     * Apply model filters.
     */
    public function scopeFilter(Builder $query, BaseFilters $filters = null): Builder
    {
        if (! $filters) {
            $filters = App::make($this->filter);
        }

        return $filters->apply($query);
    }

    /**
     * Override pagination per page.
     */
    public function getPerPage(): int
    {
        return request('perPage', parent::getPerPage());
    }
}