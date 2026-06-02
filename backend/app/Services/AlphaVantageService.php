<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AlphaVantageService
{
    private const CACHE_TTL_SECONDS = 3600;

    public function search(string $keywords): array
    {
        $normalized = strtolower(trim($keywords));

        return Cache::remember(
            "alphavantage:search:us:{$normalized}",
            self::CACHE_TTL_SECONDS,
            fn () => $this->fetchSearch($normalized),
        );
    }

    private function fetchSearch(string $keywords): array
    {
        $response = Http::get(config('services.alphavantage.base_url'), [
            'function' => 'SYMBOL_SEARCH',
            'keywords' => $keywords,
            'apikey' => config('services.alphavantage.key'),
        ]);

        if ($response->failed()) {
            Log::warning('Alpha Vantage SYMBOL_SEARCH HTTP failure', [
                'status' => $response->status(),
                'keywords' => $keywords,
            ]);
            return [];
        }

        $data = $response->json();

        // Rate-limit / informational responses come back 200 OK without `bestMatches`.
        if (! isset($data['bestMatches'])) {
            if (isset($data['Note']) || isset($data['Information'])) {
                Log::warning('Alpha Vantage SYMBOL_SEARCH throttled', [
                    'message' => $data['Note'] ?? $data['Information'],
                    'keywords' => $keywords,
                ]);
            }
            return [];
        }

        $usMatches = array_values(array_filter(
            $data['bestMatches'],
            fn (array $match) => ($match['4. region'] ?? null) === 'United States',
        ));

        return array_map(fn (array $match) => [
            'symbol' => $match['1. symbol'] ?? '',
            'name' => $match['2. name'] ?? '',
            'type' => $match['3. type'] ?? '',
            'region' => $match['4. region'] ?? '',
            'currency' => $match['8. currency'] ?? '',
            'matchScore' => $match['9. matchScore'] ?? '',
        ], $usMatches);
    }
}
