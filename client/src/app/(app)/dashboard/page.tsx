"use client";

import { useAuth } from "@/context/AuthContext";
import { StockSearch } from "@/components/StockSearch";

export default function DashboardPage() {
  const { user } = useAuth();
  if (!user) return null;

  return (
    <div className="mx-auto w-full sm:w-3/4 xl:w-1/2">
      <div className="py-6">
        <StockSearch /> 
      </div>
    </div>
  );
}
