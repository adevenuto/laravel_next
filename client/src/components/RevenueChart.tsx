"use client";

import dynamic from "next/dynamic";
import type { ApexOptions } from "apexcharts";
import type { QuarterlyRevenue } from "@/lib/api";

const ApexChart = dynamic(() => import("react-apexcharts"), { ssr: false });

const BAR_COLOR = "#f59e0b";
const HIGHLIGHT_COLOR = "#fbbf24";
const Y_AXIS_WIDTH = 56;
const RIGHT_PADDING = 12;
const MAX_QUARTERS = 20;

function parseQuarter(fiscalDateEnding: string): {
  full: string;
  quarter: string;
  year: number;
} {
  const [yearStr, monthStr] = fiscalDateEnding.split("-");
  const year = parseInt(yearStr ?? "", 10);
  const month = parseInt(monthStr ?? "", 10);
  if (!year || !month) {
    return { full: fiscalDateEnding, quarter: fiscalDateEnding, year: 0 };
  }
  const q = Math.ceil(month / 3);
  return { full: `Q${q} ${year}`, quarter: `Q${q}`, year };
}

function formatRevenue(value: number): string {
  if (!isFinite(value)) return "—";
  if (Math.abs(value) >= 1e12) return `$${(value / 1e12).toFixed(2)}T`;
  if (Math.abs(value) >= 1e9) return `$${(value / 1e9).toFixed(2)}B`;
  if (Math.abs(value) >= 1e6) return `$${(value / 1e6).toFixed(2)}M`;
  return `$${value.toLocaleString()}`;
}

export function RevenueChart({ quarters }: { quarters: QuarterlyRevenue[] }) {
  const points = quarters
    .slice(0, MAX_QUARTERS)
    .map((q) => ({
      ...parseQuarter(q.fiscalDateEnding),
      value: parseFloat(q.totalRevenue),
    }))
    .filter((p) => isFinite(p.value))
    .reverse();

  if (points.length === 0) return null;

  const yearGroups: { year: number; count: number }[] = [];
  for (const p of points) {
    const last = yearGroups[yearGroups.length - 1];
    if (last && last.year === p.year) {
      last.count++;
    } else {
      yearGroups.push({ year: p.year, count: 1 });
    }
  }

  const lastIndex = points.length - 1;
  const seriesData = points.map((p, i) => ({
    x: p.full,
    y: p.value,
    fillColor: i === lastIndex ? HIGHLIGHT_COLOR : BAR_COLOR,
  }));

  const options: ApexOptions = {
    chart: {
      type: "bar",
      toolbar: { show: false },
      fontFamily: "inherit",
      background: "transparent",
    },
    plotOptions: {
      bar: {
        borderRadius: 2,
        columnWidth: "85%",
      },
    },
    dataLabels: { enabled: false },
    grid: { borderColor: "hsl(var(--border))", strokeDashArray: 3 },
    xaxis: {
      labels: {
        style: { colors: "hsl(var(--muted-foreground))", fontSize: "6px" },
        formatter: (val) => String(val).split(" ")[0],
      },
      axisBorder: { show: false },
      axisTicks: { show: false },
    },
    yaxis: {
      labels: {
        style: { colors: "hsl(var(--muted-foreground))", fontSize: "10px" },
        formatter: (v: number) => formatRevenue(v),
        minWidth: Y_AXIS_WIDTH,
        maxWidth: Y_AXIS_WIDTH,
      },
    },
    tooltip: {
      theme: "light",
      y: { formatter: (v: number) => formatRevenue(v) },
    },
  };

  return (
    <div className="w-full">
      <div className="mb-3 text-sm font-medium">Quarterly revenue</div>
      <div
        className="mb-1 flex text-xs font-medium text-muted-foreground"
        style={{ paddingLeft: Y_AXIS_WIDTH, paddingRight: RIGHT_PADDING }}
      >
        {yearGroups.map((g) => (
          <div key={g.year} style={{ flex: g.count }} className="text-center">
            {g.year}
          </div>
        ))}
      </div>
      <ApexChart
        options={options}
        series={[{ name: "Revenue", data: seriesData }]}
        type="bar"
        height={360}
      />
    </div>
  );
}
