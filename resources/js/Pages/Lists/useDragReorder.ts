import { useEffect, useRef, type RefObject } from 'react';

interface DragState {
    isDragging: boolean;
    draggedCard: HTMLElement | null;
    placeholder: HTMLElement | null;
    offsetY: number;
    startY: number;
    holdTimer: ReturnType<typeof setTimeout> | null;
}

export function useDragReorder(
    containerRef: RefObject<HTMLDivElement | null>,
    onReorder: (albumIds: number[]) => void,
): void {
    const stateRef = useRef<DragState>({
        isDragging: false,
        draggedCard: null,
        placeholder: null,
        offsetY: 0,
        startY: 0,
        holdTimer: null,
    });

    const onReorderRef = useRef(onReorder);
    onReorderRef.current = onReorder;

    useEffect(() => {
        const container = containerRef.current;
        if (!container) return;

        const state = stateRef.current;

        function getCards(): HTMLElement[] {
            return Array.from(container!.querySelectorAll('.album-card'));
        }

        function createPlaceholder(height: number): HTMLElement {
            const el = document.createElement('div');
            el.className =
                'drag-placeholder rounded-xl border-2 border-dashed border-zinc-300 dark:border-zinc-600';
            el.style.height = `${height}px`;
            return el;
        }

        function startDrag(card: HTMLElement, clientY: number): void {
            state.isDragging = true;
            state.draggedCard = card;

            const rect = card.getBoundingClientRect();
            state.offsetY = clientY - rect.top;

            state.placeholder = createPlaceholder(rect.height);
            card.parentNode!.insertBefore(state.placeholder, card);

            card.style.position = 'fixed';
            card.style.top = `${clientY - state.offsetY}px`;
            card.style.left = `${rect.left}px`;
            card.style.width = `${rect.width}px`;
            card.style.zIndex = '100';
            card.style.opacity = '0.9';
            card.style.boxShadow = '0 10px 25px -5px rgba(0,0,0,0.2)';
            card.style.pointerEvents = 'none';
            card.style.transition = 'none';
            card.classList.add('cursor-grabbing');

            document.body.style.userSelect = 'none';
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            (document.body.style as any).webkitUserSelect =
                'none';
        }

        function moveDrag(clientY: number): void {
            if (!state.isDragging || !state.draggedCard || !state.placeholder)
                return;

            state.draggedCard.style.top = `${clientY - state.offsetY}px`;

            const cards = getCards();
            for (const card of cards) {
                if (card === state.draggedCard) continue;

                const rect = card.getBoundingClientRect();
                const midY = rect.top + rect.height / 2;

                if (clientY < midY) {
                    card.parentNode!.insertBefore(state.placeholder, card);
                    return;
                }
            }

            const lastCard = cards
                .filter((c) => c !== state.draggedCard)
                .pop();
            if (lastCard) {
                lastCard.parentNode!.insertBefore(
                    state.placeholder,
                    lastCard.nextSibling,
                );
            }
        }

        function endDrag(): void {
            if (state.holdTimer) {
                clearTimeout(state.holdTimer);
                state.holdTimer = null;
            }

            if (
                !state.isDragging ||
                !state.draggedCard ||
                !state.placeholder
            ) {
                state.isDragging = false;
                return;
            }

            state.placeholder.parentNode!.insertBefore(
                state.draggedCard,
                state.placeholder,
            );
            state.placeholder.remove();

            state.draggedCard.style.position = '';
            state.draggedCard.style.top = '';
            state.draggedCard.style.left = '';
            state.draggedCard.style.width = '';
            state.draggedCard.style.zIndex = '';
            state.draggedCard.style.opacity = '';
            state.draggedCard.style.boxShadow = '';
            state.draggedCard.style.pointerEvents = '';
            state.draggedCard.style.transition = '';
            state.draggedCard.classList.remove('cursor-grabbing');

            document.body.style.userSelect = '';
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            (document.body.style as any).webkitUserSelect =
                '';

            const cards = getCards();
            const albumIds = cards
                .map((card) => card.dataset.albumDbId)
                .filter((id): id is string => id !== undefined)
                .map((id) => parseInt(id, 10));

            if (albumIds.length > 0) {
                onReorderRef.current(albumIds);
            }

            state.draggedCard = null;
            state.placeholder = null;
            state.isDragging = false;
        }

        function handleMouseDown(e: MouseEvent): void {
            const handle = (e.target as HTMLElement).closest('.drag-handle');
            if (!handle) return;

            const card = handle.closest('.album-card') as HTMLElement | null;
            if (!card) return;

            e.preventDefault();
            startDrag(card, e.clientY);
        }

        function handleMouseMove(e: MouseEvent): void {
            if (state.isDragging) {
                e.preventDefault();
                moveDrag(e.clientY);
            }
        }

        function handleMouseUp(): void {
            if (state.isDragging) {
                endDrag();
            }
        }

        function handleTouchStart(e: TouchEvent): void {
            const handle = (e.target as HTMLElement).closest('.drag-handle');
            if (!handle) return;

            const card = handle.closest('.album-card') as HTMLElement | null;
            if (!card) return;

            const touch = e.touches[0];
            state.startY = touch.clientY;

            state.holdTimer = setTimeout(() => {
                startDrag(card, touch.clientY);
            }, 200);
        }

        function handleTouchMove(e: TouchEvent): void {
            if (state.holdTimer && !state.isDragging) {
                const touch = e.touches[0];
                if (Math.abs(touch.clientY - state.startY) > 10) {
                    clearTimeout(state.holdTimer);
                    state.holdTimer = null;
                }
                return;
            }

            if (state.isDragging) {
                e.preventDefault();
                moveDrag(e.touches[0].clientY);
            }
        }

        function handleTouchEnd(): void {
            if (state.holdTimer) {
                clearTimeout(state.holdTimer);
                state.holdTimer = null;
            }
            if (state.isDragging) {
                endDrag();
            }
        }

        container.addEventListener('mousedown', handleMouseDown);
        document.addEventListener('mousemove', handleMouseMove);
        document.addEventListener('mouseup', handleMouseUp);
        container.addEventListener('touchstart', handleTouchStart, {
            passive: true,
        });
        container.addEventListener('touchmove', handleTouchMove, {
            passive: false,
        });
        container.addEventListener('touchend', handleTouchEnd);
        container.addEventListener('touchcancel', handleTouchEnd);

        return () => {
            container.removeEventListener('mousedown', handleMouseDown);
            document.removeEventListener('mousemove', handleMouseMove);
            document.removeEventListener('mouseup', handleMouseUp);
            container.removeEventListener('touchstart', handleTouchStart);
            container.removeEventListener('touchmove', handleTouchMove);
            container.removeEventListener('touchend', handleTouchEnd);
            container.removeEventListener('touchcancel', handleTouchEnd);
        };
    }, [containerRef]);
}
