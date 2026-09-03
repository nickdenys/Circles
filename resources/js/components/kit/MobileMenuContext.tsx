/**
 * Lets the TopBar open the mobile sidebar drawer without every page having to
 * thread an `onMenu` prop through. The layout owns the state and provides it;
 * the TopBar consumes it and renders a hamburger when the viewport is mobile.
 */
import { createContext, PropsWithChildren, useContext } from 'react';

export interface MobileMenuContextValue {
    open: boolean;
    openMenu: () => void;
    closeMenu: () => void;
}

const MobileMenuContext = createContext<MobileMenuContextValue | null>(null);

export function MobileMenuProvider({
    value,
    children,
}: PropsWithChildren<{ value: MobileMenuContextValue }>) {
    return <MobileMenuContext.Provider value={value}>{children}</MobileMenuContext.Provider>;
}

/** Returns null outside the authenticated layout, where there is no drawer to open. */
export function useMobileMenu(): MobileMenuContextValue | null {
    return useContext(MobileMenuContext);
}
