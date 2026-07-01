import { CSSProperties, useState } from 'react';

import { Label } from '@/components/hoopify/Label';

interface DialogFieldProps {
    id: string;
    label: string;
    value: string;
    onChange: (value: string) => void;
    error?: string;
    optional?: boolean;
    multiline?: boolean;
    autoFocus?: boolean;
}

function fieldStyle(focused: boolean, invalid: boolean): CSSProperties {
    return {
        width: '100%',
        boxSizing: 'border-box',
        padding: '11px 14px',
        borderRadius: 10,
        border: `1.5px solid ${invalid ? 'var(--critical)' : focused ? 'var(--accent)' : 'var(--line-strong)'}`,
        boxShadow: focused && !invalid ? '0 0 0 3px var(--accent-weak)' : 'none',
        background: 'var(--surface-2)',
        fontFamily: 'var(--font-sans)',
        fontSize: 15,
        fontWeight: 500,
        color: 'var(--fg1)',
        outline: 'none',
        transition: 'border-color var(--dur-fast), box-shadow var(--dur-fast)',
    };
}

export function DialogField({
    id,
    label,
    value,
    onChange,
    error,
    optional,
    multiline,
    autoFocus,
}: DialogFieldProps) {
    const [focused, setFocused] = useState(false);
    const invalid = !!error;

    return (
        <div>
            <label htmlFor={id} style={{ display: 'block', marginBottom: 8 }}>
                <Label ink>
                    {label}
                    {optional && <span style={{ color: 'var(--fg3)' }}> (optional)</span>}
                </Label>
            </label>
            {multiline ? (
                <textarea
                    id={id}
                    value={value}
                    autoFocus={autoFocus}
                    rows={3}
                    aria-invalid={invalid || undefined}
                    onChange={(event) => onChange(event.target.value)}
                    onFocus={() => setFocused(true)}
                    onBlur={() => setFocused(false)}
                    style={{ ...fieldStyle(focused, invalid), minHeight: 92, resize: 'vertical', lineHeight: 1.5 }}
                />
            ) : (
                <input
                    id={id}
                    type="text"
                    value={value}
                    autoFocus={autoFocus}
                    aria-invalid={invalid || undefined}
                    onChange={(event) => onChange(event.target.value)}
                    onFocus={() => setFocused(true)}
                    onBlur={() => setFocused(false)}
                    style={fieldStyle(focused, invalid)}
                />
            )}
            {error && <p style={{ margin: '7px 0 0', fontSize: 12.5, color: 'var(--critical)' }}>{error}</p>}
        </div>
    );
}
