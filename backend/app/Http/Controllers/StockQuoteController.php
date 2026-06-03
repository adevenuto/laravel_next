<?php

namespace App\Http\Controllers;

use App\Services\AlphaVantageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class StockQuoteController extends Controller
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

        $quote = $this->alphaVantage->getQuote($symbol);

        if ($quote === null) {
            return response()->json(['message' => 'Stock not found'], 404);
        }

        return response()->json($quote);
    }
}
