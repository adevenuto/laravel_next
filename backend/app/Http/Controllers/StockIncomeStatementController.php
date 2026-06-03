<?php

namespace App\Http\Controllers;

use App\Services\AlphaVantageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class StockIncomeStatementController extends Controller
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

        $statement = $this->alphaVantage->getIncomeStatement($symbol);

        if ($statement === null) {
            return response()->json(['message' => 'Income statement not found'], 404);
        }

        return response()->json($statement);
    }
}
