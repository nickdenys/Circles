import { CSSProperties, ReactNode, useEffect } from 'react';
import { createPortal } from 'react-dom';
import { X } from 'lucide-react';

import { useIsMobile } from '@/hooks/use-is-mobile';
import { IconButton } from './IconButton';
import { Label } from './Label';

const CARD_KEYFRAMES = [
    '@keyframes circlesCardIn{from{transform:translateY(12px)}to{transform:none}}',
    '@media (prefers-reduced-motion: no-preference){',
    '  .circles-card-modal{animation:circlesCardIn var(--dur-base) var(--ease-spring)}',
    '}',
].join('\n');

interface CardModalProps {
    label?: string;
    ariaLabel?: string;
    code?: string;
    meta?: string;
    width?: number;
    footer?: ReactNode;
    closeButton?: boolean;
    onClose?: () => void;
    contentPad?: string;
    children: ReactNode;
}

/**
 * The reusable "index-card" window: a centered, hard-shadow dialog with a mono
 * catalog header strip and an optional bordered footer. Closes on scrim click
 * and Escape. Renders into a portal on document.body so it is never clipped by
 * the page's transformed (drag-and-drop) containers.
 *
 * Below 760px it becomes a bottom sheet: full width, anchored to the bottom
 * edge, rounded on top only, and slid up from below.
 */
export function CardModal({
    label = 'CARD',
    ariaLabel,
    code,
    meta,
    width = 600,
    footer,
    closeButton = true,
    onClose,
    contentPad = '26px 30px 30px',
    children,
}: CardModalProps) {
    const isMobile = useIsMobile();

    useEffect(() => {
        function onKey(event: KeyboardEvent) {
            if (event.key === 'Escape' && onClose) {
                onClose();
            }
        }
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [onClose]);

    useEffect(() => {
        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        return () => {
            document.body.style.overflow = previousOverflow;
        };
    }, []);

    if (typeof document === 'undefined') {
        return null;
    }

    const sheet: CSSProperties | null = isMobile
        ? {
              width: '100%',
              maxWidth: 'none',
              maxHeight: '92dvh',
              borderRadius: '16px 16px 0 0',
              borderBottom: 'none',
              boxShadow: 'var(--shadow-lg)',
              animation: 'sheetIn var(--dur-base) var(--ease-out) both',
          }
        : null;

    return createPortal(
        <div
            onClick={(event) => {
                if (event.target === event.currentTarget && onClose) {
                    onClose();
                }
            }}
            style={{
                position: 'fixed',
                inset: 0,
                zIndex: 500,
                display: 'flex',
                alignItems: isMobile ? 'flex-end' : 'center',
                justifyContent: 'center',
                padding: isMobile ? 0 : 28,
                background: 'var(--overlay)',
            }}
        >
            <style>{CARD_KEYFRAMES}</style>
            <div
                role="dialog"
                aria-modal="true"
                aria-label={ariaLabel || label || 'Dialog'}
                className={isMobile ? undefined : 'circles-card-modal'}
                style={{
                    width,
                    maxWidth: '100%',
                    maxHeight: '88%',
                    display: 'flex',
                    flexDirection: 'column',
                    background: 'var(--surface)',
                    borderRadius: 16,
                    border: '1.5px solid var(--line-ink)',
                    boxShadow: 'var(--shadow-hard)',
                    overflow: 'hidden',
                    ...sheet,
                }}
            >
                {isMobile && (
                    <span
                        aria-hidden="true"
                        style={{
                            width: 40,
                            height: 4,
                            borderRadius: 999,
                            background: 'var(--line-strong)',
                            margin: '8px auto 4px',
                            flex: 'none',
                        }}
                    />
                )}
                <div
                    style={{
                        flex: 'none',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        gap: 12,
                        padding: isMobile ? '6px 10px 6px 18px' : '11px 12px 11px 22px',
                        borderBottom: '1px solid var(--line)',
                        background: 'var(--surface-2)',
                    }}
                >
                    <Label ink>{code ? `${label} · ${code}` : label}</Label>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                        {meta && <Label>{meta}</Label>}
                        {closeButton && (
                            <IconButton
                                icon={X}
                                label="Close"
                                size={17}
                                boxSize={isMobile ? 44 : 32}
                                onClick={onClose}
                            />
                        )}
                    </div>
                </div>

                <div
                    style={{
                        flex: 1,
                        minHeight: 0,
                        overflowY: 'auto',
                        WebkitOverflowScrolling: 'touch',
                        padding: isMobile
                            ? footer
                                ? '18px 18px 22px'
                                : '18px 18px calc(28px + env(safe-area-inset-bottom))'
                            : contentPad,
                    }}
                >
                    {children}
                </div>

                {footer && (
                    <div
                        style={{
                            flex: 'none',
                            borderTop: '1px solid var(--line)',
                            padding: isMobile
                                ? '12px 18px calc(12px + env(safe-area-inset-bottom))'
                                : '14px 22px',
                            background: 'var(--surface)',
                        }}
                    >
                        {footer}
                    </div>
                )}
            </div>
        </div>,
        document.body,
    );
}
