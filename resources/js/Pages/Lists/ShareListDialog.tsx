import { router } from '@inertiajs/react';
import { Check, Copy, Globe, Link2, Loader2, Lock } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

import { Button } from '@/components/kit/Button';
import { CardModal } from '@/components/kit/CardModal';
import { Label } from '@/components/kit/Label';

interface ShareListDialogProps {
    listId: number;
    isShared: boolean;
    shareUrl: string | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export default function ShareListDialog({
    listId,
    isShared,
    shareUrl,
    open,
    onOpenChange,
}: ShareListDialogProps) {
    const [processing, setProcessing] = useState(false);
    const [copied, setCopied] = useState(false);
    const copyTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        if (open) {
            setCopied(false);
        }
    }, [open]);

    useEffect(() => () => {
        if (copyTimer.current) {
            clearTimeout(copyTimer.current);
        }
    }, []);

    /* The dialog stays open across the toggle so the link appears in place. */
    function toggleSharing() {
        const options = {
            preserveScroll: true,
            preserveState: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
        };

        if (isShared) {
            router.delete(`/lists/${listId}/share`, options);
            return;
        }

        router.post(`/lists/${listId}/share`, {}, options);
    }

    function copyLink() {
        if (!shareUrl) {
            return;
        }

        navigator.clipboard.writeText(shareUrl).then(() => {
            setCopied(true);
            if (copyTimer.current) {
                clearTimeout(copyTimer.current);
            }
            copyTimer.current = setTimeout(() => setCopied(false), 2000);
        });
    }

    if (!open) {
        return null;
    }

    return (
        <CardModal
            label="SHARE LIST"
            ariaLabel="Share list"
            width={520}
            onClose={() => onOpenChange(false)}
            footer={
                <div style={{ display: 'flex', justifyContent: 'space-between', gap: 10 }}>
                    <Button
                        variant={isShared ? 'ghost' : 'primary'}
                        type="button"
                        icon={processing ? undefined : isShared ? Lock : Globe}
                        onClick={toggleSharing}
                        disabled={processing}
                        id="toggle-sharing-button"
                    >
                        {processing && <Loader2 size={15} strokeWidth={2} className="animate-spin" />}
                        {isShared ? 'Stop sharing' : 'Create share link'}
                    </Button>
                    <Button variant="secondary" type="button" onClick={() => onOpenChange(false)}>
                        Done
                    </Button>
                </div>
            }
        >
            <div style={{ display: 'flex', flexDirection: 'column', gap: 18 }}>
                <p style={{ margin: 0, fontSize: 14.5, lineHeight: 1.6, color: 'var(--fg2)' }}>
                    {isShared
                        ? 'Anyone with this link can see this list, its albums, and any notes or scores on them. They see nothing else in your library, and they do not need an account.'
                        : 'Create a link that shows this list, its albums, and any notes or scores on them to anyone who opens it. No account needed, and nothing else in your library is reachable from it.'}
                </p>

                {isShared && shareUrl && (
                    <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
                        <Label accent>Share link</Label>
                        <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
                            <input
                                id="share-link-input"
                                readOnly
                                value={shareUrl}
                                onFocus={(event) => event.currentTarget.select()}
                                style={{
                                    flex: 1,
                                    minWidth: 0,
                                    fontFamily: 'var(--font-mono)',
                                    fontSize: 12.5,
                                    padding: '11px 12px',
                                    borderRadius: 10,
                                    border: '1px solid var(--line-strong)',
                                    background: 'var(--surface-2)',
                                    color: 'var(--fg1)',
                                }}
                            />
                            <Button
                                variant="secondary"
                                type="button"
                                icon={copied ? Check : Copy}
                                onClick={copyLink}
                                id="copy-share-link-button"
                            >
                                {copied ? 'Copied' : 'Copy'}
                            </Button>
                        </div>
                        <Label style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
                            <Link2 size={12} strokeWidth={2} />
                            This link never expires
                        </Label>
                    </div>
                )}
            </div>
        </CardModal>
    );
}
