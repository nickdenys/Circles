import { router } from '@inertiajs/react';
import axios from 'axios';
import { Check, Loader2, Trash2 } from 'lucide-react';
import { CSSProperties, FormEvent, useEffect, useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/components/kit/Button';
import { CardModal } from '@/components/kit/CardModal';
import { MiniCover } from '@/components/kit/CoverArt';
import { Label } from '@/components/kit/Label';
import { ScoreReadout, StarRating } from '@/components/kit/StarRating';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Textarea } from '@/components/ui/textarea';

export interface RatingDialogAlbum {
    id: number;
    title: string;
    artist?: string | null;
    coverUrl?: string | null;
    releaseDate?: string | null;
    albumType?: string | null;
    totalTracks?: number | null;
    currentRating?: number | null;
    currentReview?: string | null;
}

interface RatingDialogProps {
    listId: number;
    album: RatingDialogAlbum | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onSubmitted: (albumId: number, rating: number, review: string | null) => void;
    allowUnreview?: boolean;
    onUnreviewed?: (albumId: number) => void;
    onMoveUndone?: (albumId: number) => void;
}

function reviewFieldStyle(focused: boolean): CSSProperties {
    return {
        width: '100%',
        minHeight: 104,
        resize: 'none',
        padding: '13px 15px',
        borderRadius: 10,
        border: `1.5px solid ${focused ? 'var(--accent)' : 'var(--line-strong)'}`,
        boxShadow: focused ? '0 0 0 3px var(--accent-weak)' : 'none',
        background: 'var(--surface-2)',
        fontFamily: 'var(--font-sans)',
        fontSize: 14.5,
        color: 'var(--fg1)',
        lineHeight: 1.55,
        transition: 'border-color var(--dur-fast), box-shadow var(--dur-fast)',
    };
}

export default function RatingDialog({
    listId,
    album,
    open,
    onOpenChange,
    onSubmitted,
    allowUnreview = false,
    onUnreviewed,
    onMoveUndone,
}: RatingDialogProps) {
    const [rating, setRating] = useState<number>(0);
    const [review, setReview] = useState<string>('');
    const [reviewFocused, setReviewFocused] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [confirmUnreviewOpen, setConfirmUnreviewOpen] = useState(false);

    useEffect(() => {
        if (open && album) {
            setRating(album.currentRating ?? 0);
            setReview(album.currentReview ?? '');
            setError(null);
            setReviewFocused(false);
        }
    }, [open, album]);

    function handleSubmit(event: FormEvent) {
        event.preventDefault();

        if (!album || rating < 0.5) {
            setError('Pick at least half a star.');

            return;
        }

        setProcessing(true);
        setError(null);

        axios
            .post(`/lists/${listId}/albums/${album.id}/review`, {
                rating,
                review: review.trim() === '' ? null : review.trim(),
            })
            .then((response) => {
                const submittedRating = response.data?.rating ?? rating;
                const submittedReview = response.data?.review ?? (review.trim() === '' ? null : review.trim());
                const reviewedListId = response.data?.reviewedListId;
                const reviewedListSlug = response.data?.reviewedListSlug;

                if (reviewedListId && reviewedListId !== listId) {
                    const movedAlbum = album;
                    toast.success(`Moved "${movedAlbum.title}" to Reviewed.`, {
                        action: {
                            label: 'Undo',
                            onClick: () => undoReview(reviewedListId, movedAlbum),
                        },
                        cancel: reviewedListSlug
                            ? {
                                  label: 'View list',
                                  onClick: () => router.visit(`/lists/${reviewedListSlug}`),
                              }
                            : undefined,
                    });
                }

                onSubmitted(album.id, submittedRating, submittedReview);
                onOpenChange(false);
            })
            .catch(() => {
                toast.error('Could not save your review.');
                setError('Could not save your review. Please try again.');
            })
            .finally(() => {
                setProcessing(false);
            });
    }

    function undoReview(reviewedListId: number, movedAlbum: RatingDialogAlbum) {
        axios
            .delete(`/lists/${reviewedListId}/albums/${movedAlbum.id}/review`, {
                params: { restore_to_source: 1 },
            })
            .then(() => {
                toast.success(`Restored "${movedAlbum.title}".`);
                onMoveUndone?.(movedAlbum.id);
            })
            .catch(() => {
                toast.error('Could not undo your review.');
            });
    }

    function handleUnreview() {
        if (!album) { return; }

        setProcessing(true);

        axios
            .delete(`/lists/${listId}/albums/${album.id}/review`)
            .then((response) => {
                const listenLaterListSlug = response.data?.listenLaterListSlug;

                toast.success(`Moved "${album.title}" to Listen Later.`, listenLaterListSlug
                    ? {
                          action: {
                              label: 'View list',
                              onClick: () => router.visit(`/lists/${listenLaterListSlug}`),
                          },
                      }
                    : undefined);

                onUnreviewed?.(album.id);
                setConfirmUnreviewOpen(false);
                onOpenChange(false);
            })
            .catch(() => {
                toast.error('Could not un-review this album.');
            })
            .finally(() => {
                setProcessing(false);
            });
    }

    function handleOpenChange(value: boolean) {
        if (!value && !processing) {
            setRating(0);
            setReview('');
            setError(null);
        }
        onOpenChange(value);
    }

    const dirty =
        !!album && (rating !== (album.currentRating ?? 0) || review !== (album.currentReview ?? ''));
    const canSave = dirty && rating >= 0.5 && !processing;

    const metadataParts = album
        ? [
              album.releaseDate ? album.releaseDate.slice(0, 4) : null,
              album.albumType ? album.albumType.toUpperCase() : null,
              album.totalTracks ? `${album.totalTracks} TRACKS` : null,
          ].filter((part): part is string => Boolean(part))
        : [];

    return (
        <>
            {open && album && (
                <CardModal
                    label=""
                    ariaLabel="Review card"
                    width={600}
                    onClose={() => handleOpenChange(false)}
                    footer={
                        <div
                            style={{
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: allowUnreview ? 'space-between' : 'flex-end',
                                gap: 12,
                            }}
                        >
                            {allowUnreview && (
                                <Button
                                    variant="danger"
                                    size="sm"
                                    type="button"
                                    icon={Trash2}
                                    id="unreview-button"
                                    onClick={() => setConfirmUnreviewOpen(true)}
                                    disabled={processing}
                                >
                                    Un-review
                                </Button>
                            )}
                            <div style={{ display: 'flex', gap: 10 }}>
                                <Button
                                    variant="ghost"
                                    type="button"
                                    onClick={() => handleOpenChange(false)}
                                    disabled={processing}
                                >
                                    Cancel
                                </Button>
                                <Button
                                    variant="primary"
                                    type="submit"
                                    form="rating-dialog-form"
                                    icon={processing ? undefined : Check}
                                    disabled={!canSave}
                                >
                                    {processing && (
                                        <Loader2 size={15} strokeWidth={2} className="animate-spin" />
                                    )}
                                    Save review
                                </Button>
                            </div>
                        </div>
                    }
                >
                    <form
                        id="rating-dialog-form"
                        onSubmit={handleSubmit}
                        style={{ display: 'flex', flexDirection: 'column', gap: 24 }}
                    >
                        <div style={{ display: 'flex', gap: 18, alignItems: 'center' }}>
                            <div
                                style={{
                                    width: 92,
                                    height: 92,
                                    flex: 'none',
                                    borderRadius: 12,
                                    overflow: 'hidden',
                                    border: '1px solid var(--line)',
                                    boxShadow: 'var(--shadow-md)',
                                }}
                            >
                                <MiniCover
                                    src={album.coverUrl}
                                    alt={album.title}
                                    size={92}
                                    radius={0}
                                    style={{ width: 92, height: 92 }}
                                />
                            </div>
                            <div style={{ minWidth: 0 }}>
                                <h2
                                    style={{
                                        fontFamily: 'var(--font-display)',
                                        fontWeight: 800,
                                        fontSize: 30,
                                        letterSpacing: '-0.025em',
                                        margin: 0,
                                        lineHeight: 1.02,
                                    }}
                                >
                                    {album.title}
                                </h2>
                                {album.artist && (
                                    <div
                                        style={{
                                            fontSize: 16,
                                            color: 'var(--fg2)',
                                            fontWeight: 500,
                                            marginTop: 4,
                                        }}
                                    >
                                        {album.artist}
                                    </div>
                                )}
                                {metadataParts.length > 0 && (
                                    <Label style={{ display: 'block', marginTop: 8 }}>
                                        {metadataParts.join(' · ')}
                                    </Label>
                                )}
                            </div>
                        </div>

                        <div
                            style={{
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'space-between',
                                gap: 16,
                                padding: '18px 20px',
                                borderRadius: 12,
                                background: 'var(--surface-2)',
                                border: '1px solid var(--line)',
                            }}
                        >
                            <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
                                <Label>Your rating</Label>
                                <StarRating
                                    value={rating}
                                    onChange={(next) => {
                                        setRating(next);
                                        setError(null);
                                    }}
                                    size={38}
                                    gap={8}
                                />
                            </div>
                            <div style={{ textAlign: 'right' }}>
                                <ScoreReadout value={rating} size={48} />
                                <Label style={{ display: 'block', marginTop: 4, fontSize: 10 }}>
                                    {rating ? '' : 'tap to rate'}
                                </Label>
                            </div>
                        </div>

                        {error && (
                            <p style={{ margin: '-12px 0 0', fontSize: 13, color: 'var(--critical)' }}>
                                {error}
                            </p>
                        )}

                        <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
                            <Label>Review</Label>
                            <div style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
                                <Textarea
                                    id="rating-dialog-review"
                                    value={review}
                                    onChange={(event) => setReview(event.target.value)}
                                    onFocus={() => setReviewFocused(true)}
                                    onBlur={() => setReviewFocused(false)}
                                    rows={1}
                                    placeholder="Sit with it, then write. Your words, filed with the album."
                                    style={reviewFieldStyle(reviewFocused)}
                                />
                                <div style={{ display: 'flex', justifyContent: 'flex-end' }}>
                                    <Label style={{ fontSize: 10 }}>
                                        {review.trim().length} chars · saved to this album
                                    </Label>
                                </div>
                            </div>
                        </div>
                    </form>
                </CardModal>
            )}

            <AlertDialog open={confirmUnreviewOpen} onOpenChange={setConfirmUnreviewOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Un-review this album?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Your rating and review for{' '}
                            <span className="font-medium text-foreground">
                                {album?.title}
                            </span>{' '}
                            will be permanently deleted, and the album will be
                            moved back to your Listen Later list. This action
                            cannot be undone.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel disabled={processing}>
                            Cancel
                        </AlertDialogCancel>
                        <AlertDialogAction
                            variant="destructive"
                            onClick={handleUnreview}
                            disabled={processing}
                        >
                            {processing ? 'Un-reviewing...' : 'Un-review'}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}
