import { ChevronRight, Menu } from 'lucide-react';
import { Fragment, ReactNode } from 'react';
import { useIsMobile } from '@/hooks/use-is-mobile';
import { IconButton } from './IconButton';
import { Label } from './Label';
import { useMobileMenu } from './MobileMenuContext';

interface TopBarProps {
    crumbs: string[];
    children?: ReactNode;
}

export function TopBar({ crumbs, children }: TopBarProps) {
    const isMobile = useIsMobile();
    const mobileMenu = useMobileMenu();

    /* On mobile the rail is off-canvas, so the bar trades breadcrumbs for a menu button. */
    const shown = isMobile ? crumbs.slice(-1) : crumbs;

    return (
        <div
            style={{
                position: 'sticky',
                top: 0,
                zIndex: 50,
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
                gap: 8,
                padding: isMobile ? '6px 10px 6px 6px' : '12px 40px',
                minHeight: 56,
                boxSizing: 'border-box',
                borderBottom: '1px solid var(--line)',
                background: 'color-mix(in srgb, var(--bg) 82%, transparent)',
                backdropFilter: 'blur(10px)',
            }}
        >
            <div
                style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: isMobile ? 4 : 10,
                    minWidth: 0,
                }}
            >
                {isMobile && mobileMenu && (
                    <IconButton
                        icon={Menu}
                        label="Open menu"
                        onClick={mobileMenu.openMenu}
                        size={21}
                        boxSize={44}
                    />
                )}
                {shown.map((crumb, i) => (
                    <Fragment key={i}>
                        {i > 0 && (
                            <ChevronRight
                                size={14}
                                strokeWidth={2}
                                style={{ color: 'var(--line-strong)', flex: 'none' }}
                            />
                        )}
                        {i < shown.length - 1 ? (
                            <Label style={{ whiteSpace: 'nowrap' }}>{crumb}</Label>
                        ) : (
                            <span
                                style={{
                                    fontSize: 13,
                                    fontWeight: 600,
                                    color: 'var(--fg1)',
                                    whiteSpace: 'nowrap',
                                    overflow: 'hidden',
                                    textOverflow: 'ellipsis',
                                }}
                            >
                                {crumb}
                            </span>
                        )}
                    </Fragment>
                ))}
            </div>
            {children && (
                <div style={{ display: 'flex', alignItems: 'center', gap: 6 }}>{children}</div>
            )}
        </div>
    );
}
