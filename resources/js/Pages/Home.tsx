import { usePage } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface MostPopulatedList {
    title: string;
    albumsCount: number;
}

interface HomeProps {
    totalLists: number;
    totalAlbums: number;
    mostPopulatedList: MostPopulatedList | null;
    auth: {
        user: {
            id: number;
            name: string;
            avatar: string | null;
        };
    };
    [key: string]: unknown;
}

export default function Home() {
    const { totalLists, totalAlbums, mostPopulatedList, auth } =
        usePage<HomeProps>().props;

    return (
        <div>
            <h1 className="text-3xl font-bold tracking-tight sm:text-4xl">
                Welcome back, {auth.user.name}
            </h1>

            <div className="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-normal text-muted-foreground">
                            Total Lists
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-2xl font-semibold">{totalLists}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-normal text-muted-foreground">
                            Total Albums
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-2xl font-semibold">{totalAlbums}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-normal text-muted-foreground">
                            Most Populated List
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {mostPopulatedList && mostPopulatedList.albumsCount > 0 ? (
                            <>
                                <p className="text-2xl font-semibold">
                                    {mostPopulatedList.title}
                                </p>
                                <p className="mt-0.5 text-sm text-muted-foreground">
                                    {mostPopulatedList.albumsCount}{' '}
                                    {mostPopulatedList.albumsCount === 1
                                        ? 'album'
                                        : 'albums'}
                                </p>
                            </>
                        ) : (
                            <p className="text-lg text-muted-foreground">&mdash;</p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}
