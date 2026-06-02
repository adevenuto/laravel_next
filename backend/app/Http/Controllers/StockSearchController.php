<?php

namespace App\Http\Controllers;

use App\Services\SecCompanyTickerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class StockSearchController extends Controller
{
    public function __construct(private SecCompanyTickerService $tickers)
    {
    }

    public function __invoke(string $term): JsonResponse
    {
        $term = trim($term);

        if ($term === '' || mb_strlen($term) > 50) {
            throw ValidationException::withMessages([
                'term' => ['Search term must be 1-50 characters.'],
            ]);
        }

        return response()->json([
            'results' => $this->tickers->search($term),
        ]);
    }
}
