<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SecCompanyTickerService
{
    private const CACHE_KEY = 'sec:company_tickers:v1';
    private const CACHE_TTL_SECONDS = 86400;
    private const DEFAULT_LIMIT = 15;

    public function search(string $query, int $limit = self::DEFAULT_LIMIT): array
    {
        $normalized = strtolower(trim($query));
        if ($normalized === '') {
            return [];
        }

        $scored = [];
        foreach ($this->getTickers() as $entry) {
            $score = $this->score($entry, $normalized);
            if ($score > 0) {
                $scored[] = ['score' => $score, 'entry' => $entry];
            }
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_map(
            fn ($row) => $this->toMatch($row['entry'], $row['score']),
            array_slice($scored, 0, $limit),
        );
    }

    public function getTickers(): array
    {
        return Cache::remember(
            self::CACHE_KEY,
            self::CACHE_TTL_SECONDS,
            fn () => $this->fetchAndNormalize(),
        );
    }

    private function fetchAndNormalize(): array
    {
        $response = Http::withHeaders([
            'User-Agent' => config('services.sec.user_agent'),
        ])->get(config('services.sec.tickers_url'));

        if ($response->failed()) {
            Log::warning('SEC company tickers fetch failed', [
                'status' => $response->status(),
            ]);
            return [];
        }

        $data = $response->json();
        if (! is_array($data)) {
            Log::warning('SEC company tickers response not an array');
            return [];
        }

        return array_values(array_map(
            fn ($row) => [
                'cik' => $row['cik_str'] ?? null,
                'ticker' => (string) ($row['ticker'] ?? ''),
                'name' => (string) ($row['title'] ?? ''),
            ],
            $data,
        ));
    }

    private function score(array $entry, string $query): int
    {
        $ticker = strtolower($entry['ticker']);
        $name = strtolower($entry['name']);

        if ($ticker === '' && $name === '') {
            return 0;
        }

        if ($ticker === $query) {
            return 1000;
        }

        if ($ticker !== '' && str_starts_with($ticker, $query)) {
            return 500 - (strlen($ticker) - strlen($query));
        }

        if ($name !== '') {
            foreach (preg_split('/\s+/', $name) ?: [] as $word) {
                if ($word !== '' && str_starts_with($word, $query)) {
                    return 100;
                }
            }

            if (str_contains($name, $query)) {
                return 50;
            }
        }

        return 0;
    }

    private function toMatch(array $entry, int $score): array
    {
        return [
            'symbol' => $entry['ticker'],
            'name' => $entry['name'],
            'type' => 'Equity',
            'region' => 'United States',
            'currency' => 'USD',
            'matchScore' => number_format(min($score / 1000, 1), 4),
        ];
    }
}
