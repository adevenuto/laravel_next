<?php

namespace App\Http\Controllers;

use App\Services\AlphaVantageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class StockSearchController extends Controller
{
    public function __construct(private AlphaVantageService $alphaVantage)
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
            'results' => $this->alphaVantage->search($term),
        ]);
    }
}
