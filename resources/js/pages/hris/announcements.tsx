import { Form, Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';

type Option = { id: number; name: string };
type Announcement = {
    id: number;
    title: string;
    body: string;
    audience: string;
    published_at?: string;
    is_read: boolean;
};
export default function Announcements({
    announcements,
    canManage,
    departments,
    locations,
}: {
    announcements: { data: Announcement[] };
    canManage: boolean;
    departments: Option[];
    locations: Option[];
}) {
    return (
        <>
            <Head title="Pengumuman" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Pengumuman</h1>
                    <p className="text-muted-foreground">
                        Informasi internal untuk seluruh tim.
                    </p>
                </div>
                {canManage && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Buat pengumuman</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Form
                                action="/announcements"
                                method="post"
                                className="grid gap-3"
                            >
                                <Input
                                    name="title"
                                    placeholder="Judul"
                                    required
                                />
                                <textarea
                                    name="body"
                                    className="min-h-24 rounded-md border bg-background p-3"
                                    required
                                />
                                <div className="grid gap-3 md:grid-cols-4">
                                    <select
                                        name="audience"
                                        className="h-9 rounded-md border bg-background px-3"
                                    >
                                        <option value="all">Semua</option>
                                        <option value="manager">Manager</option>
                                        <option value="employee">
                                            Employee
                                        </option>
                                    </select>
                                    <select
                                        name="department_id"
                                        className="h-9 rounded-md border bg-background px-3"
                                    >
                                        <option value="">
                                            Semua departemen
                                        </option>
                                        {departments.map((item) => (
                                            <option
                                                key={item.id}
                                                value={item.id}
                                            >
                                                {item.name}
                                            </option>
                                        ))}
                                    </select>
                                    <select
                                        name="location_id"
                                        className="h-9 rounded-md border bg-background px-3"
                                    >
                                        <option value="">Semua lokasi</option>
                                        {locations.map((item) => (
                                            <option
                                                key={item.id}
                                                value={item.id}
                                            >
                                                {item.name}
                                            </option>
                                        ))}
                                    </select>
                                    <Input
                                        name="published_at"
                                        type="datetime-local"
                                    />
                                    <Button>Simpan</Button>
                                </div>
                            </Form>
                        </CardContent>
                    </Card>
                )}
                <div className="grid gap-4 md:grid-cols-2">
                    {announcements.data.map((item) => (
                        <Card
                            key={item.id}
                            className={
                                item.is_read
                                    ? 'opacity-80'
                                    : 'border-primary/40'
                            }
                        >
                            <CardHeader>
                                <CardTitle>{item.title}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-sm whitespace-pre-line">
                                    {item.body}
                                </p>
                                <div className="mt-4 flex items-center justify-between">
                                    <p className="text-xs text-muted-foreground">
                                        Audiens: {item.audience}
                                    </p>
                                    {!item.is_read && item.published_at && (
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={() =>
                                                router.post(
                                                    `/announcements/${item.id}/read`,
                                                )
                                            }
                                        >
                                            Tandai dibaca
                                        </Button>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </>
    );
}
