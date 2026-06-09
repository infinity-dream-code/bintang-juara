<?php

namespace App\Support;

use Illuminate\Http\Request;

class FilterHandler
{
    public static function resolveFilters($filter, $allowedFilters): array
    {
        $normalized = collect($allowedFilters)
            ->mapWithKeys(function ($value, $key) {
                return is_int($key)
                    ? [$value => $value]
                    : [$key => $value];
            })
            ->toArray();

        return collect($filter)
            ->only(array_keys($normalized))
            ->map(function ($value) {
                if (is_array($value)) {
                    $value = collect($value)
                        ->filter(fn($v) => !in_array(strtolower((string)$v), ['all', '']))
                        ->values()
                        ->all();
                } else {
                    if (in_array(strtolower((string)$value), ['all', ''])) {
                        return null;
                    }
                }
                return $value;
            })
            ->reject(function ($value) {
                return $value === 'all'
                    || $value === null
                    || $value === ''
                    || (is_array($value) && empty($value));
            })

            ->mapWithKeys(fn($value, $key) => [
                $normalized[$key] => $value
            ])
            ->sortKeys()
            ->toArray();
    }
}
