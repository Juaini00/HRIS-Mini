import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

type Item = {
    id: string;
    data: { title: string; message: string; url?: string };
    read_at?: string;
    created_at: string;
};
export default function Notifications({
    notifications,
}: {
    notifications: { data: Item[] };
}) {
    return (
        <>
            <Head title="Notifikasi" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Notifikasi</h1>
                        <p className="text-muted-foreground">
                            Pembaruan workflow dan informasi penting.
                        </p>
                    </div>
                    <Button
                        variant="outline"
                        onClick={() => router.post('/notifications/read-all')}
                    >
                        Tandai semua dibaca
                    </Button>
                </div>
                <Card>
                    <CardContent className="grid gap-1 p-2">
                        {notifications.data.map((item) => (
                            <button
                                key={item.id}
                                type="button"
                                onClick={() =>
                                    router.post(
                                        `/notifications/${item.id}/read`,
                                        {},
                                        {
                                            onSuccess: () =>
                                                item.data.url &&
                                                router.visit(item.data.url),
                                        },
                                    )
                                }
                                className={`rounded-lg p-4 text-left hover:bg-muted ${item.read_at ? 'opacity-70' : 'bg-primary/5'}`}
                            >
                                <p className="font-medium">{item.data.title}</p>
                                <p className="text-sm text-muted-foreground">
                                    {item.data.message}
                                </p>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    {item.created_at}
                                </p>
                            </button>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
