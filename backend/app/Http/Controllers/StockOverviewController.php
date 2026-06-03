<?php

namespace App\Http\Controllers;

use App\Services\AlphaVantageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class StockOverviewController extends Controller
{
    public function __construct(private AlphaVantageService $alphaVantage)
    {
    }

    public function __invoke(string $symbol): JsonResponse
    {
        $symbol = strtoupper(trim($symbol));

        if ($symbol === '' || ! preg_match('/^[A-Z0-9.\-]{1,10}$/', $symbol)) {
            throw ValidationException::withMessages([
                'symbol' => ['Invalid ticker symbol.'],
            ]);
        }

        $overview = $this->alphaVantage->getOverview($symbol);

        if ($overview === null) {
            return response()->json(['message' => 'Stock not found'], 404);
        }

        return response()->json($overview);
    }
}
