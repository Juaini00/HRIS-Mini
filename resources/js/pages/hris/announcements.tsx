import { Form, Head, router } from '@inertiajs/react';
import { InfoHint } from '@/components/info-hint';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

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
                                className="grid gap-5"
                            >
                                <div className="grid gap-1.5">
                                    <Label htmlFor="title">Judul</Label>
                                    <Input
                                        id="title"
                                        name="title"
                                        placeholder="Contoh: Libur Idul Fitri 2026"
                                        required
                                    />
                                    <p className="text-xs text-muted-foreground">
                                        Judul singkat yang tampil sebagai kepala
                                        kartu pengumuman.
                                    </p>
                                </div>
                                <div className="grid gap-1.5">
                                    <Label htmlFor="body">Isi pengumuman</Label>
                                    <textarea
                                        id="body"
                                        name="body"
                                        placeholder="Tulis detail informasi yang perlu diketahui tim…"
                                        className="min-h-24 rounded-md border bg-background p-3 text-sm"
                                        required
                                    />
                                </div>

                                <div className="grid gap-3">
                                    <p className="text-sm font-medium">
                                        Siapa yang menerima
                                    </p>
                                    <div className="grid gap-4 md:grid-cols-3">
                                        <div className="grid gap-1.5">
                                            <Label
                                                htmlFor="audience"
                                                className="flex items-center gap-1.5"
                                            >
                                                Peran
                                                <InfoHint text="Batasi pengumuman berdasarkan peran: semua orang, hanya manager, atau hanya karyawan." />
                                            </Label>
                                            <select
                                                id="audience"
                                                name="audience"
                                                className="h-9 rounded-md border bg-background px-3 text-sm"
                                            >
                                                <option value="all">
                                                    Semua orang
                                                </option>
                                                <option value="manager">
                                                    Hanya manager
                                                </option>
                                                <option value="employee">
                                                    Hanya karyawan
                                                </option>
                                            </select>
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label
                                                htmlFor="department_id"
                                                className="flex items-center gap-1.5"
                                            >
                                                Departemen
                                                <InfoHint text="Kirim hanya ke satu departemen. Kosongkan untuk menjangkau semua departemen." />
                                            </Label>
                                            <select
                                                id="department_id"
                                                name="department_id"
                                                className="h-9 rounded-md border bg-background px-3 text-sm"
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
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label
                                                htmlFor="location_id"
                                                className="flex items-center gap-1.5"
                                            >
                                                Lokasi
                                                <InfoHint text="Kirim hanya ke satu lokasi kantor. Kosongkan untuk menjangkau semua lokasi." />
                                            </Label>
                                            <select
                                                id="location_id"
                                                name="location_id"
                                                className="h-9 rounded-md border bg-background px-3 text-sm"
                                            >
                                                <option value="">
                                                    Semua lokasi
                                                </option>
                                                {locations.map((item) => (
                                                    <option
                                                        key={item.id}
                                                        value={item.id}
                                                    >
                                                        {item.name}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div className="grid gap-1.5">
                                    <Label
                                        htmlFor="published_at"
                                        className="flex items-center gap-1.5"
                                    >
                                        Waktu terbit
                                        <InfoHint text="Kosongkan untuk menyimpan sebagai draft. Isi dengan waktu sekarang untuk langsung terbit, atau waktu mendatang untuk menjadwalkan." />
                                    </Label>
                                    <Input
                                        id="published_at"
                                        name="published_at"
                                        type="datetime-local"
                                        className="sm:max-w-xs"
                                    />
                                </div>

                                <Button className="justify-self-start">
                                    Simpan pengumuman
                                </Button>
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
