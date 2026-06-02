export function Footer() {
  return (
    <footer className="border-t bg-background">
      <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-6 text-center text-sm text-muted-foreground border">
        © {new Date().getFullYear()} Next + Laravel
      </div>
    </footer>
  );
}
