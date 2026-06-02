"use client";

import { useEffect, useRef, useState } from "react";
import { Search } from "lucide-react";
import { Input } from "@/components/ui/input";
import { api, type StockMatch } from "@/lib/api";
import { useDebounce } from "@/hooks/useDebounce";

export function StockSearch() {
  const [query, setQuery] = useState("");
  const [results, setResults] = useState<StockMatch[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [isOpen, setIsOpen] = useState(false);
  const [hasSearched, setHasSearched] = useState(false);
  const containerRef = useRef<HTMLDivElement>(null);

  const debouncedQuery = useDebounce(query, 300);

  useEffect(() => {
    const term = debouncedQuery.trim();
    if (term === "") {
      setResults([]);
      setHasSearched(false);
      setIsOpen(false);
      return;
    }

    const controller = new AbortController();
    setIsLoading(true);
    setIsOpen(true);

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

  useEffect(() => {
    function handleClickOutside(e: MouseEvent) {
      if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
        setIsOpen(false);
      }
    }
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  function handleSelect(match: StockMatch) {
    setIsOpen(false);
    // eslint-disable-next-line no-console
    console.log("[StockSearch] selected:", match.symbol);
  }

  return (
    <div ref={containerRef} className="relative w-full">
      <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
      <Input
        type="text"
        placeholder="Search stocks by company name or ticker..."
        value={query}
        onChange={(e) => setQuery(e.target.value)}
        onFocus={() => {
          if (query.trim() !== "" && (results.length > 0 || hasSearched)) {
            setIsOpen(true);
          }
        }}
        onKeyDown={(e) => {
          if (e.key === "Escape") setIsOpen(false);
        }}
        className="pl-9"
      />

      {isOpen && (
        <ul
          role="listbox"
          className="absolute left-0 right-0 top-full z-50 mt-2 max-h-80 overflow-y-auto rounded-md border bg-background shadow-lg"
        >
          {isLoading && (
            <li className="px-4 py-3 text-sm text-muted-foreground">Searching…</li>
          )}
          {!isLoading && results.length === 0 && hasSearched && (
            <li className="px-4 py-3 text-sm text-muted-foreground">No results</li>
          )}
          {!isLoading &&
            results.map((match) => (
              <li
                key={`${match.symbol}-${match.region}`}
                role="option"
                aria-selected="false"
                onClick={() => handleSelect(match)}
                className="flex cursor-pointer items-center justify-between gap-4 px-4 py-3 hover:bg-accent"
              >
                <div className="min-w-0">
                  <div className="font-medium">{match.symbol}</div>
                  <div className="truncate text-sm text-muted-foreground">{match.name}</div>
                </div>
                <div className="shrink-0 text-xs text-muted-foreground">{match.region}</div>
              </li>
            ))}
        </ul>
      )}
    </div>
  );
}
