<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AlphaVantageService
{
    private const CACHE_TTL_SECONDS = 14400;
    private const MIN_REQUEST_INTERVAL_MS = 1500;
    private const LAST_REQUEST_KEY = 'alphavantage:last_request_at';

    /**
     * Sleep until at least MIN_REQUEST_INTERVAL_MS has passed since the last
     * outbound Alpha Vantage HTTP call. AV's free tier enforces ~1 req/sec;
     * giving 1.5s headroom keeps us well clear of the throttle.
     */
    private function throttle(): void
    {
        $lastAt = Cache::get(self::LAST_REQUEST_KEY);
        if ($lastAt !== null) {
            $elapsedMs = (microtime(true) - (float) $lastAt) * 1000;
            $waitMs = self::MIN_REQUEST_INTERVAL_MS - $elapsedMs;
            if ($waitMs > 0) {
                usleep((int) ($waitMs * 1000));
            }
        }
        Cache::put(self::LAST_REQUEST_KEY, microtime(true), 60);
    }

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
        $this->throttle();
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

    public function getOverview(string $symbol): ?array
    {
        $normalized = strtoupper(trim($symbol));
        if ($normalized === '') {
            return null;
        }

        return Cache::remember(
            "alphavantage:overview:{$normalized}",
            self::CACHE_TTL_SECONDS,
            fn () => $this->fetchOverview($normalized),
        );
    }

    private function fetchOverview(string $symbol): ?array
    {
        $this->throttle();
        $response = Http::get(config('services.alphavantage.base_url'), [
            'function' => 'OVERVIEW',
            'symbol' => $symbol,
            'apikey' => config('services.alphavantage.key'),
        ]);

        if ($response->failed()) {
            Log::warning('Alpha Vantage OVERVIEW HTTP failure', [
                'status' => $response->status(),
                'symbol' => $symbol,
            ]);
            return null;
        }

        $data = $response->json();

        if (! is_array($data) || empty($data)) {
            return null;
        }

        if (isset($data['Note']) || isset($data['Information'])) {
            Log::warning('Alpha Vantage OVERVIEW throttled', [
                'message' => $data['Note'] ?? $data['Information'],
                'symbol' => $symbol,
            ]);
            return null;
        }

        if (! isset($data['Symbol'])) {
            return null;
        }

        return [
            'symbol' => $data['Symbol'],
            'name' => $data['Name'] ?? '',
            'description' => $data['Description'] ?? '',
            'exchange' => $data['Exchange'] ?? '',
            'currency' => $data['Currency'] ?? '',
            'country' => $data['Country'] ?? '',
            'sector' => $data['Sector'] ?? '',
            'industry' => $data['Industry'] ?? '',
            'marketCap' => $data['MarketCapitalization'] ?? '',
            'peRatio' => $data['PERatio'] ?? '',
            'pegRatio' => $data['PEGRatio'] ?? '',
            'eps' => $data['EPS'] ?? '',
            'dividendPerShare' => $data['DividendPerShare'] ?? '',
            'dividendYield' => $data['DividendYield'] ?? '',
            'bookValue' => $data['BookValue'] ?? '',
            'fiftyTwoWeekHigh' => $data['52WeekHigh'] ?? '',
            'fiftyTwoWeekLow' => $data['52WeekLow'] ?? '',
        ];
    }

    public function getQuote(string $symbol): ?array
    {
        $normalized = strtoupper(trim($symbol));
        if ($normalized === '') {
            return null;
        }

        return Cache::remember(
            "alphavantage:quote:{$normalized}",
            self::CACHE_TTL_SECONDS,
            fn () => $this->fetchQuote($normalized),
        );
    }

    private function fetchQuote(string $symbol): ?array
    {
        $this->throttle();
        $response = Http::get(config('services.alphavantage.base_url'), [
            'function' => 'GLOBAL_QUOTE',
            'symbol' => $symbol,
            'apikey' => config('services.alphavantage.key'),
        ]);

        if ($response->failed()) {
            Log::warning('Alpha Vantage GLOBAL_QUOTE HTTP failure', [
                'status' => $response->status(),
                'symbol' => $symbol,
            ]);
            return null;
        }

        $data = $response->json();

        if (! is_array($data) || empty($data)) {
            return null;
        }

        if (isset($data['Note']) || isset($data['Information'])) {
            Log::warning('Alpha Vantage GLOBAL_QUOTE throttled', [
                'message' => $data['Note'] ?? $data['Information'],
                'symbol' => $symbol,
            ]);
            return null;
        }

        $quote = $data['Global Quote'] ?? null;
        if (! is_array($quote) || empty($quote)) {
            return null;
        }

        return [
            'symbol' => $quote['01. symbol'] ?? '',
            'open' => $quote['02. open'] ?? '',
            'high' => $quote['03. high'] ?? '',
            'low' => $quote['04. low'] ?? '',
            'price' => $quote['05. price'] ?? '',
            'volume' => $quote['06. volume'] ?? '',
            'latestTradingDay' => $quote['07. latest trading day'] ?? '',
            'previousClose' => $quote['08. previous close'] ?? '',
            'change' => $quote['09. change'] ?? '',
            'changePercent' => $quote['10. change percent'] ?? '',
        ];
    }
}
