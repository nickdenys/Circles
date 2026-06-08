import { router } from '@inertiajs/react';
import axios from 'axios';
import { Loader2, Trash2 } from 'lucide-react';
import { FormEvent, useEffect, useState } from 'react';
import { toast } from 'sonner';

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
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { StarRating } from '@/components/ui/star-rating';
import { Textarea } from '@/components/ui/textarea';

export interface RatingDialogAlbum {
    id: number;
    title: string;
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
}

export default function RatingDialog({
    listId,
    album,
    open,
    onOpenChange,
    onSubmitted,
    allowUnreview = false,
    onUnreviewed,
}: RatingDialogProps) {
    const [rating, setRating] = useState<number>(0);
    const [review, setReview] = useState<string>('');
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [confirmUnreviewOpen, setConfirmUnreviewOpen] = useState(false);

    useEffect(() => {
        if (open && album) {
            setRating(album.currentRating ?? 0);
            setReview(album.currentReview ?? '');
            setError(null);
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

                if (reviewedListId && reviewedListId !== listId) {
                    toast.success(`Moved "${album.title}" to Reviewed.`, {
                        action: {
                            label: 'View list',
                            onClick: () => router.visit(`/lists/${reviewedListId}`),
                        },
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

    function handleUnreview() {
        if (!album) { return; }

        setProcessing(true);

        axios
            .delete(`/lists/${listId}/albums/${album.id}/review`)
            .then((response) => {
                const listenLaterListId = response.data?.listenLaterListId;

                toast.success(`Moved "${album.title}" to Listen Later.`, listenLaterListId
                    ? {
                          action: {
                              label: 'View list',
                              onClick: () => router.visit(`/lists/${listenLaterListId}`),
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

    return (
        <>
            <Dialog open={open} onOpenChange={handleOpenChange}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Rate &amp; review</DialogTitle>
                        <DialogDescription>
                            How would you rate{' '}
                            <span className="font-medium text-foreground">
                                {album?.title}
                            </span>
                            ?
                        </DialogDescription>
                    </DialogHeader>

                    <form
                        id="rating-dialog-form"
                        onSubmit={handleSubmit}
                        className="space-y-5"
                    >
                        <div className="space-y-2">
                            <Label>Rating</Label>
                            <StarRating
                                value={rating}
                                size="lg"
                                interactive
                                onChange={setRating}
                            />
                            {error && (
                                <p className="text-sm text-destructive">{error}</p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="rating-dialog-review">
                                Review{' '}
                                <span className="font-normal text-muted-foreground">
                                    (optional)
                                </span>
                            </Label>
                            <Textarea
                                id="rating-dialog-review"
                                value={review}
                                onChange={(event) => setReview(event.target.value)}
                                rows={4}
                                placeholder="Anything you want to remember about it?"
                            />
                        </div>

                        <DialogFooter className="sm:justify-between">
                            {allowUnreview ? (
                                <Button
                                    type="button"
                                    variant="destructive"
                                    id="unreview-button"
                                    onClick={() => setConfirmUnreviewOpen(true)}
                                    disabled={processing}
                                >
                                    <Trash2 className="mr-1 h-3 w-3" />
                                    Un-review &amp; move to Listen Later
                                </Button>
                            ) : (
                                <span />
                            )}

                            <div className="flex gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => handleOpenChange(false)}
                                    disabled={processing}
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing && (
                                        <Loader2 className="mr-1 h-3 w-3 animate-spin" />
                                    )}
                                    Save
                                </Button>
                            </div>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

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
