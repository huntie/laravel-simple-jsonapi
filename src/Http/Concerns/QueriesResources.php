<?php

namespace Huntie\JsonApi\Http\Concerns;

use Schema;

trait QueriesResources
{
    /**
     * Sort a resource query by one or more attributes.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array                                 $attributes
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function sortQuery($query, array $attributes)
    {
        foreach ($attributes as $expression) {
            $direction = substr($expression, 0, 1) === '-' ? 'desc' : 'asc';
            $column = preg_replace('/^\-/', '', $expression);
            $query = $query->orderBy($column, $direction);
        }

        return $query;
    }

    /**
     * Filter a resource query by one or more attributes.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array                                 $attributes
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function filterQuery($query, array $attributes)
    {
        $searchableColumns = array_diff(
            Schema::getColumnListing($query->getModel()->getTable()),
            $query->getModel()->getHidden()
        );

        foreach (array_intersect_key($attributes, array_flip($searchableColumns)) as $column => $value) {
            if (is_numeric($value)) {
                // Exact numeric match
                $query = $query->where($column, $value);
            } else if (in_array(strtolower($value), ['true', 'false'])) {
                // Boolean match
                $query = $query->where($column, filter_var($value, FILTER_VALIDATE_BOOLEAN));
            } else {
                $query = $query->where($column, $value);
            }
        }

        return $query;
    }
}
