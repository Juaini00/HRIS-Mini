import { Form, Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
type Department = {
    id: number;
    name: string;
    code: string;
    employees_count: number;
};
type Item = { id: number; name: string; is_active?: boolean };
export default function Organization({
    departments,
    positions,
    locations,
    employmentTypes,
    leaveTypes,
    holidays,
}: {
    departments: Department[];
    positions: Array<Item & { department: Department }>;
    locations: Item[];
    employmentTypes: Item[];
    leaveTypes: Item[];
    holidays: Array<Item & { date: string }>;
}) {
    return (
        <>
            <Head title="Organisasi" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold">
                        Struktur organisasi
                    </h1>
                    <p className="text-muted-foreground">
                        Kelola master data perusahaan dan kebijakan cuti.
                    </p>
                </div>
                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Tipe kepegawaian</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4">
                            <Form
                                action="/organization/employment-types"
                                method="post"
                                className="flex gap-2"
                            >
                                <Input
                                    name="name"
                                    placeholder="Permanent, Contract..."
                                    required
                                />
                                <Button>Tambah</Button>
                            </Form>
                            {employmentTypes.map((item) => (
                                <div key={item.id} className="border-t pt-2">
                                    {item.name}
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Departemen</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4">
                            <Form
                                action="/organization/departments"
                                method="post"
                                className="flex gap-2"
                            >
                                <Input
                                    name="code"
                                    placeholder="Kode"
                                    required
                                />
                                <Input
                                    name="name"
                                    placeholder="Nama departemen"
                                    required
                                />
                                <Button>Tambah</Button>
                            </Form>
                            {departments.map((x) => (
                                <div
                                    key={x.id}
                                    className="flex justify-between border-t pt-2"
                                >
                                    <span>
                                        {x.code} · {x.name}
                                    </span>
                                    <span className="text-muted-foreground">
                                        {x.employees_count} orang
                                    </span>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Posisi</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4">
                            <Form
                                action="/organization/positions"
                                method="post"
                                className="flex gap-2"
                            >
                                <select
                                    name="department_id"
                                    className="h-9 rounded-md border bg-background px-3"
                                >
                                    {departments.map((x) => (
                                        <option key={x.id} value={x.id}>
                                            {x.name}
                                        </option>
                                    ))}
                                </select>
                                <Input
                                    name="name"
                                    placeholder="Nama posisi"
                                    required
                                />
                                <Button>Tambah</Button>
                            </Form>
                            {positions.map((x) => (
                                <div key={x.id} className="border-t pt-2">
                                    {x.name} ·{' '}
                                    <span className="text-muted-foreground">
                                        {x.department.name}
                                    </span>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Lokasi</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4">
                            <Form
                                action="/organization/locations"
                                method="post"
                                className="flex gap-2"
                            >
                                <Input
                                    name="name"
                                    placeholder="Lokasi"
                                    required
                                />
                                <Input
                                    name="timezone"
                                    defaultValue="Asia/Makassar"
                                    required
                                />
                                <Button>Tambah</Button>
                            </Form>
                            {locations.map((x) => (
                                <div key={x.id} className="border-t pt-2">
                                    {x.name}
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Jenis cuti</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4">
                            <Form
                                action="/organization/leave-types"
                                method="post"
                                className="grid grid-cols-2 gap-2"
                            >
                                <Input
                                    name="name"
                                    placeholder="Jenis cuti"
                                    required
                                />
                                <Input
                                    name="annual_quota"
                                    type="number"
                                    placeholder="Kuota"
                                    required
                                />
                                <label className="flex items-center gap-2 text-sm">
                                    <input
                                        name="is_paid"
                                        type="checkbox"
                                        value="1"
                                    />
                                    Dibayar
                                </label>
                                <label className="flex items-center gap-2 text-sm">
                                    <input
                                        name="requires_attachment"
                                        type="checkbox"
                                        value="1"
                                    />
                                    Wajib lampiran
                                </label>
                                <Button>Tambah</Button>
                            </Form>
                            {leaveTypes.map((x) => (
                                <div key={x.id} className="border-t pt-2">
                                    {x.name}
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>Hari libur</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4">
                            <Form
                                action="/organization/holidays"
                                method="post"
                                className="flex gap-2"
                            >
                                <Input name="date" type="date" required />
                                <Input
                                    name="name"
                                    placeholder="Nama hari libur"
                                    required
                                />
                                <Button>Tambah</Button>
                            </Form>
                            {holidays.map((x) => (
                                <div
                                    key={x.id}
                                    className="flex justify-between border-t pt-2"
                                >
                                    <span>{x.name}</span>
                                    <span>{x.date}</span>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
