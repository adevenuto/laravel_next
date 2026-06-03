"use client";

import { use, useEffect, useState } from "react";
import { api, type StockOverview, type StockQuote } from "@/lib/api";
import { FiftyTwoWeekRange } from "@/components/FiftyTwoWeekRange";

function formatLargeUsd(value: string): string {
  const num = parseFloat(value);
  if (!isFinite(num) || num === 0) return "—";
  if (num >= 1e12) return `$${(num / 1e12).toFixed(2)}T`;
  if (num >= 1e9) return `$${(num / 1e9).toFixed(2)}B`;
  if (num >= 1e6) return `$${(num / 1e6).toFixed(2)}M`;
  return `$${num.toLocaleString()}`;
}

function formatPercent(value: string): string {
  const num = parseFloat(value);
  if (!isFinite(num)) return "—";
  return `${(num * 100).toFixed(2)}%`;
}

function Stat({ label, value }: { label: string; value: string }) {
  return (
    <div className="rounded-md border p-3">
      <div className="text-xs text-muted-foreground">{label}</div>
      <div className="mt-1 text-sm font-medium">{value || "—"}</div>
    </div>
  );
}

function isAbortError(err: unknown): boolean {
  return err instanceof DOMException && err.name === "AbortError";
}

export default function StockShowPage({
  params,
}: {
  params: Promise<{ symbol: string }>;
}) {
  const { symbol } = use(params);
  const ticker = symbol.toUpperCase();

  const [overview, setOverview] = useState<StockOverview | null>(null);
  const [quote, setQuote] = useState<StockQuote | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const controller = new AbortController();
    setIsLoading(true);
    setError(null);
    setOverview(null);
    setQuote(null);

    // Sequence the two Alpha Vantage calls — AV's free tier enforces ~1 req/sec
    // burst limit, so parallel fetches get one of them throttled (and the null
    // result isn't cached, masking the failure until refresh).
    (async () => {
      try {
        const overviewData = await api.getStockOverview(ticker, controller.signal);
        if (controller.signal.aborted) return;
        setOverview(overviewData);
      } catch (err) {
        if (isAbortError(err)) return;
        setError(err instanceof Error ? err.message : "Failed to load");
        setIsLoading(false);
        return;
      }

      try {
        const quoteData = await api.getStockQuote(ticker, controller.signal);
        if (controller.signal.aborted) return;
        setQuote(quoteData);
      } catch (err) {
        if (isAbortError(err)) return;
        // Quote failure (likely throttle) — the range bar just won't render.
      }

      if (!controller.signal.aborted) setIsLoading(false);
    })();

    return () => controller.abort();
  }, [ticker]);

  return (
    <div className="mx-auto w-full space-y-6 py-6 sm:w-3/4 xl:w-1/2">
      <header>
        <h1 className="text-3xl font-semibold tracking-tight">{ticker}</h1>
        {overview && (
          <p className="mt-1 text-muted-foreground">{overview.name}</p>
        )}
      </header>

      {isLoading && (
        <p className="text-sm text-muted-foreground">Loading…</p>
      )}

      {error && !isLoading && (
        <p className="text-sm text-destructive">{error}</p>
      )}

      {overview && !isLoading && (
        <>
          <div className="flex flex-wrap gap-x-3 gap-y-1 text-sm text-muted-foreground">
            {overview.sector && <span>{overview.sector}</span>}
            {overview.industry && <span>· {overview.industry}</span>}
            {overview.exchange && <span>· {overview.exchange}</span>}
          </div>

          {quote && (
            <FiftyTwoWeekRange
              low={parseFloat(overview.fiftyTwoWeekLow)}
              high={parseFloat(overview.fiftyTwoWeekHigh)}
              current={parseFloat(quote.price)}
            />
          )}

          <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <Stat label="Market Cap" value={formatLargeUsd(overview.marketCap)} />
            <Stat label="P/E" value={overview.peRatio} />
            <Stat label="EPS" value={overview.eps} />
            <Stat label="Div Yield" value={formatPercent(overview.dividendYield)} />
          </div>

          {overview.description && (
            <section>
              <h2 className="mb-2 text-sm font-medium">About</h2>
              <p className="text-sm leading-relaxed text-muted-foreground">
                {overview.description}
              </p>
            </section>
          )}
        </>
      )}
    </div>
  );
}
