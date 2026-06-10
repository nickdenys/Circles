import { ChevronRight } from 'lucide-react';
import { Fragment, ReactNode } from 'react';
import { Label } from './Label';

interface TopBarProps {
    crumbs: string[];
    children?: ReactNode;
}

export function TopBar({ crumbs, children }: TopBarProps) {
    return (
        <div
            style={{
                position: 'sticky',
                top: 0,
                zIndex: 50,
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
                padding: '12px 40px',
                minHeight: 56,
                boxSizing: 'border-box',
                borderBottom: '1px solid var(--line)',
                background: 'color-mix(in srgb, var(--bg) 82%, transparent)',
                backdropFilter: 'blur(10px)',
            }}
        >
            <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                {crumbs.map((crumb, i) => (
                    <Fragment key={i}>
                        {i > 0 && (
                            <ChevronRight
                                size={14}
                                strokeWidth={2}
                                style={{ color: 'var(--line-strong)', flex: 'none' }}
                            />
                        )}
                        {i < crumbs.length - 1 ? (
                            <Label style={{ whiteSpace: 'nowrap' }}>{crumb}</Label>
                        ) : (
                            <span
                                style={{
                                    fontSize: 13,
                                    fontWeight: 600,
                                    color: 'var(--fg1)',
                                    whiteSpace: 'nowrap',
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
