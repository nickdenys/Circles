import { InfiniteScroll, Link } from '@inertiajs/react';
import { ChevronRight, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';

interface AlbumListItem {
    id: number;
    title: string;
    albumsCount: number;
    url: string;
}

interface PaginatedLists {
    data: AlbumListItem[];
    next_page_url: string | null;
}

interface ListsIndexProps {
    lists: PaginatedLists;
    [key: string]: unknown;
}

export default function Index({ lists }: ListsIndexProps) {
    return (
        <div>
            <div className="flex items-center justify-between">
                <h1 className="text-3xl font-bold tracking-tight sm:text-4xl">
                    My Lists
                </h1>
                <Button asChild>
                    <Link href="/lists" data-add-list>
                        Add List
                    </Link>
                </Button>
            </div>

            {lists.data.length === 0 ? (
                <p className="mt-8 text-center text-muted-foreground">
                    No lists yet. Create one to get started.
                </p>
            ) : (
                <div className="mt-8">
                    <InfiniteScroll
                        data="lists"
                        buffer={300}
                        className="space-y-3"
                        loading={() => (
                            <div
                                id="scroll-sentinel"
                                className="flex justify-center py-4"
                            >
                                <Loader2 className="h-5 w-5 animate-spin text-muted-foreground" />
                            </div>
                        )}
                    >
                        {lists.data.map((list) => (
                            <Link
                                key={list.id}
                                href={list.url}
                                className="block"
                            >
                                <Card className="flex items-center justify-between px-5 py-4 transition hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                    <div>
                                        <p className="font-medium">
                                            {list.title}
                                        </p>
                                        <p className="mt-0.5 text-sm text-muted-foreground">
                                            {list.albumsCount}{' '}
                                            {list.albumsCount === 1
                                                ? 'album'
                                                : 'albums'}
                                        </p>
                                    </div>
                                    <ChevronRight className="h-5 w-5 text-muted-foreground" />
                                </Card>
                            </Link>
                        ))}
                    </InfiniteScroll>
                </div>
            )}
        </div>
    );
}
