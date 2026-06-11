import { Button } from '@/components/ui/button';
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

export interface SlugHistoryConflict {
    conflicting_slug: string;
    previous_owner_title: string;
    suggested_alternative: string;
}

interface ConfirmSlugOverrideDialogProps {
    open: boolean;
    conflict: SlugHistoryConflict | null;
    onUseAnyway: () => void;
    onCancel: () => void;
}

export default function ConfirmSlugOverrideDialog({
    open,
    conflict,
    onUseAnyway,
    onCancel,
}: ConfirmSlugOverrideDialogProps) {
    return (
        <AlertDialog open={open && conflict !== null} onOpenChange={(v) => !v && onCancel()}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        Take URL{' '}
                        <span className="font-mono">
                            /{conflict?.conflicting_slug}
                        </span>
                        ?
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        This URL was previously used by your{' '}
                        <span className="font-medium text-foreground">
                            "{conflict?.previous_owner_title}"
                        </span>{' '}
                        list. Visitors who follow old links will land on this
                        list instead.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel asChild>
                        <Button variant="outline" onClick={onCancel}>
                            Cancel
                        </Button>
                    </AlertDialogCancel>
                    <AlertDialogAction asChild>
                        <Button variant="destructive" onClick={onUseAnyway}>
                            Use {conflict?.conflicting_slug} anyway
                        </Button>
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
