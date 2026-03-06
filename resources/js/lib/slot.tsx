import * as React from "react"
import { mergeProps } from "@base-ui-components/react/merge-props"

interface SlotProps extends React.HTMLAttributes<HTMLElement> {
  children?: React.ReactNode
}

function Slot({ children, ...slotProps }: SlotProps) {
  if (!React.isValidElement(children)) {
    return null
  }

  return React.cloneElement(children, mergeProps(slotProps as any, children.props as any))
}

export { Slot }
