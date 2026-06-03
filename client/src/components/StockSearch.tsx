"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { Search, X } from "lucide-react";
import {
  Combobox,
  ComboboxContent,
  ComboboxEmpty,
  ComboboxInput,
  ComboboxItem,
  ComboboxList,
  ComboboxStatus,
} from "@/components/ui/combobox";
import { api, type StockMatch } from "@/lib/api";
import { useDebounce } from "@/hooks/useDebounce";

export function StockSearch() {
  const router = useRouter();
  const [query, setQuery] = useState("");
  const [results, setResults] = useState<StockMatch[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [hasSearched, setHasSearched] = useState(false);
  const [userClosed, setUserClosed] = useState(false);

  const debouncedQuery = useDebounce(query, 300);

  useEffect(() => {
    const term = debouncedQuery.trim();
    if (term === "") {
      setResults([]);
      setHasSearched(false);
      return;
    }

    const controller = new AbortController();
    setIsLoading(true);

    api
      .searchStocks(term, controller.signal)
      .then((data) => {
        setResults(data.results);
        setHasSearched(true);
      })
      .catch((err: unknown) => {
        if (err instanceof DOMException && err.name === "AbortError") return;
        setResults([]);
        setHasSearched(true);
      })
      .finally(() => {
        if (!controller.signal.aborted) setIsLoading(false);
      });

    return () => controller.abort();
  }, [debouncedQuery]);

  function handleInputValueChange(value: string, details: { reason: string }) {
    // Base UI fires this with reason='outside-press' / 'item-press' / 'escape-key'
    // on close/select to revert input to the (null) selection — ignore those.
    // Accept only changes driven by the user editing the input.
    if (details.reason !== "input-change" && details.reason !== "input-paste") {
      return;
    }
    setQuery(value);
    setUserClosed(false);
  }

  function handleClear() {
    setQuery("");
    setUserClosed(false);
  }

  function handleSelect(match: StockMatch | null) {
    if (!match) return;
    router.push(`/stocks/${encodeURIComponent(match.symbol)}`);
  }

  function handleOpenChange(open: boolean) {
    if (!open) setUserClosed(true);
  }

  const shouldOpen =
    !userClosed &&
    query.trim() !== "" &&
    (isLoading || results.length > 0 || hasSearched);

  return (
    <Combobox
      items={results}
      filter={null}
      inputValue={query}
      onInputValueChange={handleInputValueChange}
      onValueChange={handleSelect}
      itemToStringValue={(match: StockMatch) => match.symbol}
      open={shouldOpen}
      onOpenChange={handleOpenChange}
    >
      <div className="relative w-full">
        <Search className="pointer-events-none absolute left-3 top-1/2 z-10 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
        <ComboboxInput
          placeholder="Search stocks by company name or ticker..."
          className="pl-9 pr-9"
          onFocus={() => {
            if (query.trim() !== "" && (results.length > 0 || hasSearched)) {
              setUserClosed(false);
            }
          }}
        />
        {query !== "" && (
          <button
            type="button"
            aria-label="Clear search"
            onClick={handleClear}
            className="absolute right-2 top-1/2 inline-flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
          >
            <X className="h-4 w-4" />
          </button>
        )}
      </div>

      <ComboboxContent>
        <ComboboxStatus>{isLoading ? "Searching…" : null}</ComboboxStatus>
        <ComboboxEmpty>{!isLoading ? "No results" : null}</ComboboxEmpty>
        <ComboboxList>
          {(match: StockMatch) => (
            <ComboboxItem
              key={`${match.symbol}-${match.region}`}
              value={match}
              className="flex items-center justify-between gap-4 px-4 py-3"
            >
              <div className="min-w-0">
                <div className="font-medium">{match.symbol}</div>
                <div className="truncate text-sm text-muted-foreground">
                  {match.name}
                </div>
              </div>
              <div className="shrink-0 text-xs text-muted-foreground">
                {match.region}
              </div>
            </ComboboxItem>
          )}
        </ComboboxList>
      </ComboboxContent>

      
    </Combobox>
  );
}
