"use client";

import * as React from "react";
import { Combobox as ComboboxPrimitive } from "@base-ui/react/combobox";

import { cn } from "@/lib/utils";

const Combobox = ComboboxPrimitive.Root;

const ComboboxInput = React.forwardRef<
  HTMLInputElement,
  React.ComponentPropsWithoutRef<typeof ComboboxPrimitive.Input>
>(({ className, ...props }, ref) => (
  <ComboboxPrimitive.Input
    ref={ref}
    className={cn(
      "flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50",
      className,
    )}
    {...props}
  />
));
ComboboxInput.displayName = "ComboboxInput";

type ComboboxContentProps = React.ComponentPropsWithoutRef<typeof ComboboxPrimitive.Popup> &
  Pick<
    React.ComponentPropsWithoutRef<typeof ComboboxPrimitive.Positioner>,
    "side" | "sideOffset" | "align" | "alignOffset"
  >;

function ComboboxContent({
  className,
  side = "bottom",
  sideOffset = 4,
  align = "start",
  alignOffset = 0,
  ...props
}: ComboboxContentProps) {
  return (
    <ComboboxPrimitive.Portal>
      <ComboboxPrimitive.Positioner
        side={side}
        sideOffset={sideOffset}
        align={align}
        alignOffset={alignOffset}
        collisionAvoidance={{ align: "none" }}
        className="z-50"
      >
        <ComboboxPrimitive.Popup
          className={cn(
            "max-h-80 w-[var(--anchor-width)] overflow-hidden rounded-md border bg-background text-foreground shadow-lg outline-none",
            className,
          )}
          {...props}
        />
      </ComboboxPrimitive.Positioner>
    </ComboboxPrimitive.Portal>
  );
}

function ComboboxList({
  className,
  ...props
}: React.ComponentPropsWithoutRef<typeof ComboboxPrimitive.List>) {
  return (
    <ComboboxPrimitive.List
      className={cn("max-h-80 overflow-y-auto p-1", className)}
      {...props}
    />
  );
}

const ComboboxItem = React.forwardRef<
  HTMLDivElement,
  React.ComponentPropsWithoutRef<typeof ComboboxPrimitive.Item>
>(({ className, ...props }, ref) => (
  <ComboboxPrimitive.Item
    ref={ref}
    className={cn(
      "relative flex w-full cursor-pointer select-none items-center rounded-sm px-2 py-2 text-sm outline-none data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50",
      className,
    )}
    {...props}
  />
));
ComboboxItem.displayName = "ComboboxItem";

function ComboboxEmpty({
  className,
  ...props
}: React.ComponentPropsWithoutRef<typeof ComboboxPrimitive.Empty>) {
  return (
    <ComboboxPrimitive.Empty
      className={cn(
        "px-4 py-3 text-sm text-muted-foreground empty:hidden",
        className,
      )}
      {...props}
    />
  );
}

function ComboboxStatus({
  className,
  ...props
}: React.ComponentPropsWithoutRef<typeof ComboboxPrimitive.Status>) {
  return (
    <ComboboxPrimitive.Status
      className={cn(
        "px-4 py-3 text-sm text-muted-foreground empty:hidden",
        className,
      )}
      {...props}
    />
  );
}

export {
  Combobox,
  ComboboxInput,
  ComboboxContent,
  ComboboxList,
  ComboboxItem,
  ComboboxEmpty,
  ComboboxStatus,
};
