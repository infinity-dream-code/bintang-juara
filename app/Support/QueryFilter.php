<?php

namespace App\Support;

class QueryFilter
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function apply($query)
    {
        foreach ($this->filters as $filter) {
            switch ($filter['type']) {
                case 'basic':
                    $query->where(
                        $filter['column'],
                        $filter['operator'] ?? '=',
                        $filter['value']
                    );
                    break;
                case 'in':
                    $query->whereIn(
                        $filter['column'],
                        $filter['value']
                    );
                    break;
                case 'not_in':
                    $query->whereNotIn(
                        $filter['column'],
                        $filter['value']
                    );
                    break;
                case 'between':
                    $query->whereBetween(
                        $filter['column'],
                        $filter['value']
                    );
                    break;
                case 'null':
                    $query->whereNull($filter['column']);
                    break;
                case 'not_null':
                    $query->whereNotNull($filter['column']);
                    break;
                case 'or':
                    $query->orWhere(
                        $filter['column'],
                        $filter['operator'] ?? '=',
                        $filter['value']
                    );
                    break;
            }
        }

        return $query;
    }
}
