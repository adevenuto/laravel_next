export function FiftyTwoWeekRange({
  low,
  high,
  current,
}: {
  low: number;
  high: number;
  current: number;
}) {
  if (!isFinite(low) || !isFinite(high) || !isFinite(current) || high <= low) {
    return null;
  }

  const pct = Math.max(0, Math.min(1, (current - low) / (high - low))) * 100;
  const markerColor = `hsl(${pct * 1.2}, 70%, 45%)`;

  return (
    <div className="space-y-2">
      <div className="text-sm font-medium">52-week range</div>

      <div className="relative h-4">
        <span
          className="absolute text-xs font-medium"
          style={{ left: `${pct}%`, transform: `translateX(-${pct}%)` }}
        >
          ${current.toFixed(2)}
        </span>
      </div>

      <div className="relative h-4">
        <div className="absolute inset-x-0 top-1/2 h-px -translate-y-1/2 bg-border" />
        <div
          className="absolute top-1/2 size-4 rounded-full border-2 border-background"
          style={{
            left: `${pct}%`,
            transform: `translate(-${pct}%, -50%)`,
            backgroundColor: markerColor,
          }}
          aria-label={`Current price $${current.toFixed(2)}`}
        />
      </div>

      <div className="flex justify-between text-xs text-muted-foreground">
        <span>${low.toFixed(2)}</span>
        <span>${high.toFixed(2)}</span>
      </div>
    </div>
  );
}
