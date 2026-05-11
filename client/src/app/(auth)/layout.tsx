import { MainLayout } from "@/components/MainLayout";

export default function AuthLayout({ children }: { children: React.ReactNode }) {
  return (
    <MainLayout>
      <div className="flex flex-1 items-center justify-center px-4 py-12">{children}</div>
    </MainLayout>
  );
}
