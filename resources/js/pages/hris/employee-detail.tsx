import { Form, Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
type Option = { id: number; name: string };
type Document = {
    id: number;
    name: string;
    category: string;
    size: number;
    expires_at?: string;
};
type Employee = {
    id: number;
    employee_number: string;
    user: { name: string; email: string; role: string };
    department: { name: string };
    position: { name: string };
    department_id: number;
    position_id: number;
    location_id?: number;
    employment_type_id?: number;
    manager_id?: number;
    manager?: { user: { name: string } };
    phone?: string;
    joined_at: string;
    ended_at?: string;
    basic_salary?: string;
    bank_account?: string;
    documents: Document[];
    salary_histories?: Array<{
        id: number;
        amount: string;
        effective_from: string;
        effective_to?: string;
    }>;
};
export default function EmployeeDetail({
    employee,
    canUpdate,
    departments,
    positions,
    locations,
    employmentTypes,
    managers,
}: {
    employee: Employee;
    canUpdate: boolean;
    departments: Option[];
    positions: Option[];
    locations: Option[];
    employmentTypes: Option[];
    managers: Array<{ id: number; user: { name: string } }>;
}) {
    const deactivate = () => {
        const reason = window.prompt('Alasan penonaktifan');
        if (reason)
            router.patch(`/employees/${employee.id}/deactivate`, {
                ended_at: new Date().toISOString().slice(0, 10),
                reason,
            });
    };
    return (
        <>
            <Head title={employee.user.name} />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <p className="text-sm text-muted-foreground">
                            {employee.employee_number}
                        </p>
                        <h1 className="text-2xl font-semibold">
                            {employee.user.name}
                        </h1>
                        <p className="text-muted-foreground">
                            {employee.position.name} ·{' '}
                            {employee.department.name}
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/employees">Kembali</Link>
                    </Button>
                </div>
                {canUpdate && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Edit karyawan</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Form
                                action={`/employees/${employee.id}`}
                                method="put"
                                className="grid gap-3 md:grid-cols-3"
                            >
                                <Input
                                    name="name"
                                    defaultValue={employee.user.name}
                                    required
                                />
                                <Input
                                    name="email"
                                    type="email"
                                    defaultValue={employee.user.email}
                                    required
                                />
                                <Input
                                    name="phone"
                                    defaultValue={employee.phone}
                                />
                                <select
                                    name="department_id"
                                    defaultValue={employee.department_id}
                                    className="h-9 rounded-md border bg-background px-3"
                                >
                                    {departments.map((x) => (
                                        <option key={x.id} value={x.id}>
                                            {x.name}
                                        </option>
                                    ))}
                                </select>
                                <select
                                    name="position_id"
                                    defaultValue={employee.position_id}
                                    className="h-9 rounded-md border bg-background px-3"
                                >
                                    {positions.map((x) => (
                                        <option key={x.id} value={x.id}>
                                            {x.name}
                                        </option>
                                    ))}
                                </select>
                                <select
                                    name="location_id"
                                    defaultValue={employee.location_id}
                                    className="h-9 rounded-md border bg-background px-3"
                                >
                                    <option value="">Tanpa lokasi</option>
                                    {locations.map((x) => (
                                        <option key={x.id} value={x.id}>
                                            {x.name}
                                        </option>
                                    ))}
                                </select>
                                <select
                                    name="employment_type_id"
                                    defaultValue={employee.employment_type_id}
                                    className="h-9 rounded-md border bg-background px-3"
                                >
                                    <option value="">Tipe kepegawaian</option>
                                    {employmentTypes.map((item) => (
                                        <option key={item.id} value={item.id}>
                                            {item.name}
                                        </option>
                                    ))}
                                </select>
                                <select
                                    name="manager_id"
                                    defaultValue={employee.manager_id}
                                    className="h-9 rounded-md border bg-background px-3"
                                >
                                    <option value="">Tanpa manager</option>
                                    {managers.map((x) => (
                                        <option key={x.id} value={x.id}>
                                            {x.user.name}
                                        </option>
                                    ))}
                                </select>
                                <Input
                                    name="joined_at"
                                    type="date"
                                    defaultValue={employee.joined_at}
                                    required
                                />
                                <Input
                                    name="basic_salary"
                                    type="number"
                                    defaultValue={employee.basic_salary}
                                    required
                                />
                                <Input
                                    name="bank_account"
                                    defaultValue={employee.bank_account}
                                />
                                <select
                                    name="role"
                                    defaultValue={employee.user.role}
                                    className="h-9 rounded-md border bg-background px-3"
                                >
                                    <option value="employee">Employee</option>
                                    <option value="manager">Manager</option>
                                    <option value="hr_admin">HR Admin</option>
                                </select>
                                <div className="flex gap-2">
                                    <Button>Simpan</Button>
                                    {!employee.ended_at && (
                                        <Button
                                            type="button"
                                            variant="destructive"
                                            onClick={deactivate}
                                        >
                                            Nonaktifkan
                                        </Button>
                                    )}
                                </div>
                            </Form>
                        </CardContent>
                    </Card>
                )}
                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>Profil pekerjaan</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 text-sm sm:grid-cols-2">
                            <div>
                                <p className="text-muted-foreground">Email</p>
                                <p>{employee.user.email}</p>
                            </div>
                            <div>
                                <p className="text-muted-foreground">Manager</p>
                                <p>{employee.manager?.user.name ?? '-'}</p>
                            </div>
                            <div>
                                <p className="text-muted-foreground">
                                    Tanggal bergabung
                                </p>
                                <p>{employee.joined_at}</p>
                            </div>
                            <div>
                                <p className="text-muted-foreground">Status</p>
                                <p>
                                    {employee.ended_at ? 'Nonaktif' : 'Aktif'}
                                </p>
                            </div>
                            {employee.basic_salary && (
                                <div>
                                    <p className="text-muted-foreground">
                                        Gaji pokok
                                    </p>
                                    <p>
                                        Rp{' '}
                                        {Number(
                                            employee.basic_salary,
                                        ).toLocaleString('id-ID')}
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Unggah dokumen</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Form
                                action={`/employees/${employee.id}/documents`}
                                method="post"
                                encType="multipart/form-data"
                                className="grid gap-3"
                            >
                                <Input
                                    name="name"
                                    placeholder="Nama dokumen"
                                    required
                                />
                                <select
                                    name="category"
                                    className="h-9 rounded-md border bg-background px-3"
                                >
                                    <option value="contract">Kontrak</option>
                                    <option value="identity">Identitas</option>
                                    <option value="certificate">
                                        Sertifikat
                                    </option>
                                    <option value="other">Lainnya</option>
                                </select>
                                <Input
                                    name="document"
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    required
                                />
                                <Input name="expires_at" type="date" />
                                <Button>Unggah privat</Button>
                            </Form>
                        </CardContent>
                    </Card>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Dokumen privat</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-3">
                        {employee.documents.length ? (
                            employee.documents.map((document) => (
                                <div
                                    key={document.id}
                                    className="flex items-center justify-between rounded-lg border p-3"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {document.name}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {document.category} ·{' '}
                                            {(document.size / 1024).toFixed(1)}{' '}
                                            KB
                                        </p>
                                    </div>
                                    <div className="flex gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <a
                                                href={`/employee-documents/${document.id}`}
                                            >
                                                Unduh
                                            </a>
                                        </Button>
                                        {canUpdate && (
                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                onClick={() =>
                                                    router.delete(
                                                        `/employee-documents/${document.id}`,
                                                    )
                                                }
                                            >
                                                Hapus
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            ))
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                Belum ada dokumen.
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
