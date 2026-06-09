<?php
namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CacheHandler{
    public static function cacheKey(string $cacheKey, string $suffix, array $filters = [], string $search = ''): string
    {
        $key = Str::slug($cacheKey) . '_cache_version';
        $version = Cache::get($key, 1);

        $base = "{$cacheKey}:v{$version}:{$suffix}";

        foreach ($filters as $column => $value) {
            $safeColumn = str_replace('.', '-', $column);
            if (is_array($value)) {
                $value = collect($value)
                    ->map(fn($v) => Str::slug($v))
                    ->sort()
                    ->values()
                    ->implode(',');
            } else {
                $value = Str::slug($value);
            }
            $base .= ":{$safeColumn}-{$value}";
        }

        if ($search !== '') {
            $safeValue = Str::slug($search);
            $base .= ":dt_search-{$safeValue}";
        }

        return $base;
    }
}
