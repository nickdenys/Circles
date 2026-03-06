import * as React from "react"
import { Popover as PopoverPrimitive } from "@base-ui-components/react/popover"

import { cn } from "@/lib/utils"

const AnchorContext = React.createContext<React.RefObject<HTMLElement | null>>({
  current: null,
})

function Popover({
  ...props
}: React.ComponentProps<typeof PopoverPrimitive.Root>) {
  const anchorRef = React.useRef<HTMLElement | null>(null)
  return (
    <AnchorContext.Provider value={anchorRef}>
      <PopoverPrimitive.Root data-slot="popover" {...props} />
    </AnchorContext.Provider>
  )
}

function PopoverTrigger({
  ...props
}: React.ComponentProps<typeof PopoverPrimitive.Trigger>) {
  return <PopoverPrimitive.Trigger data-slot="popover-trigger" {...props} />
}

function PopoverAnchor({
  asChild,
  children,
  ...props
}: {
  asChild?: boolean
  children?: React.ReactNode
} & React.HTMLAttributes<HTMLDivElement>) {
  const anchorRef = React.useContext(AnchorContext)

  if (asChild && React.isValidElement(children)) {
    return React.cloneElement(
      children as React.ReactElement<Record<string, unknown>>,
      { ...props, ref: anchorRef }
    )
  }

  return (
    <div ref={anchorRef as React.Ref<HTMLDivElement>} data-slot="popover-anchor" {...props}>
      {children}
    </div>
  )
}

function PopoverContent({
  className,
  align = "start",
  sideOffset = 4,
  onOpenAutoFocus,
  container,
  ...props
}: React.ComponentProps<typeof PopoverPrimitive.Popup> & {
  align?: "start" | "center" | "end"
  sideOffset?: number
  onOpenAutoFocus?: (e: Event) => void
  container?: React.ComponentProps<typeof PopoverPrimitive.Portal>["container"]
}) {
  const anchorRef = React.useContext(AnchorContext)

  return (
    <PopoverPrimitive.Portal container={container}>
      <PopoverPrimitive.Positioner
        anchor={anchorRef}
        align={align}
        sideOffset={sideOffset}
      >
        <PopoverPrimitive.Popup
          data-slot="popover-content"
          initialFocus={onOpenAutoFocus ? false : undefined}
          className={cn(
            "bg-popover text-popover-foreground z-50 rounded-md border shadow-md outline-none transition-[opacity,transform] data-[ending-style]:opacity-0 data-[starting-style]:opacity-0 data-[ending-style]:scale-95 data-[starting-style]:scale-95",
            className
          )}
          {...props}
        />
      </PopoverPrimitive.Positioner>
    </PopoverPrimitive.Portal>
  )
}

export { Popover, PopoverAnchor, PopoverContent, PopoverTrigger }
