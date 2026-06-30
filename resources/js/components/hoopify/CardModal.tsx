import { ReactNode, useEffect } from 'react';
import { createPortal } from 'react-dom';
import { X } from 'lucide-react';

import { IconButton } from './IconButton';
import { Label } from './Label';

const CARD_KEYFRAMES = [
    '@keyframes hoopifyCardIn{from{transform:translateY(12px)}to{transform:none}}',
    '@media (prefers-reduced-motion: no-preference){',
    '  .hoopify-card-modal{animation:hoopifyCardIn var(--dur-base) var(--ease-spring)}',
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
                alignItems: 'center',
                justifyContent: 'center',
                padding: 28,
                background: 'var(--overlay)',
            }}
        >
            <style>{CARD_KEYFRAMES}</style>
            <div
                role="dialog"
                aria-modal="true"
                aria-label={ariaLabel || label || 'Dialog'}
                className="hoopify-card-modal"
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
                }}
            >
                <div
                    style={{
                        flex: 'none',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        gap: 12,
                        padding: '11px 12px 11px 22px',
                        borderBottom: '1px solid var(--line)',
                        background: 'var(--surface-2)',
                    }}
                >
                    <Label ink>{code ? `${label} · ${code}` : label}</Label>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                        {meta && <Label>{meta}</Label>}
                        {closeButton && (
                            <IconButton icon={X} label="Close" size={17} boxSize={32} onClick={onClose} />
                        )}
                    </div>
                </div>

                <div style={{ flex: 1, minHeight: 0, overflowY: 'auto', padding: contentPad }}>
                    {children}
                </div>

                {footer && (
                    <div
                        style={{
                            flex: 'none',
                            borderTop: '1px solid var(--line)',
                            padding: '14px 22px',
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
