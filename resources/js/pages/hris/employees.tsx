import { Form, Head, Link, router } from '@inertiajs/react';
import { useRef } from 'react';
import { CurrencyInput } from '@/components/currency-input';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
type Item = {
    id: number;
    employee_number: string;
    ended_at: string | null;
    employment_status?: string;
    user: { name: string; email: string };
    department: { name: string };
    position: { name: string };
};
const STATUS_META: Record<
    string,
    { label: string; variant: 'secondary' | 'destructive' | 'outline' }
> = {
    active: { label: 'Aktif', variant: 'secondary' },
    probation: { label: 'Probation', variant: 'outline' },
    on_leave: { label: 'Cuti panjang', variant: 'outline' },
    suspended: { label: 'Diskors', variant: 'destructive' },
    resigned: { label: 'Resign', variant: 'destructive' },
    terminated: { label: 'Diberhentikan', variant: 'destructive' },
};
type Props = {
    employees: { data: Item[] };
    departments: Array<{ id: number; name: string }>;
    positions: Array<{ id: number; name: string }>;
    locations: Array<{ id: number; name: string }>;
    employmentTypes: Array<{ id: number; name: string }>;
    canCreate: boolean;
    filters: { search: string | null; status: string };
};
export default function Employees({
    employees,
    departments,
    positions,
    locations,
    employmentTypes,
    canCreate,
    filters,
}: Props) {
    const searchDebounce = useRef<ReturnType<typeof setTimeout> | null>(null);
    const applyFilters = (next: Partial<Props['filters']>) => {
        router.get(
            '/employees',
            { search: filters.search, status: filters.status, ...next },
            { preserveState: true, replace: true },
        );
    };
    const onSearchChange = (value: string) => {
        if (searchDebounce.current) {
            clearTimeout(searchDebounce.current);
        }

        searchDebounce.current = setTimeout(
            () => applyFilters({ search: value || null }),
            300,
        );
    };

    return (
        <>
            <Head title="Karyawan" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Karyawan</h1>
                    <p className="text-muted-foreground">
                        Kelola data tenaga kerja NusaHR.
                    </p>
                </div>
                {canCreate && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Tambah karyawan</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Form
                                action="/employees"
                                method="post"
                                className="grid gap-3 md:grid-cols-3"
                            >
                                {({ errors, processing }) => (
                                    <>
                                        <Input
                                            name="name"
                                            placeholder="Nama lengkap"
                                            required
                                        />
                                        <Input
                                            name="email"
                                            type="email"
                                            placeholder="Email"
                                            required
                                        />
                                        <Input
                                            name="employee_number"
                                            placeholder="Nomor karyawan"
                                            required
                                        />
                                        <select
                                            name="department_id"
                                            className="h-9 rounded-md border bg-background px-3"
                                            required
                                        >
                                            <option value="">Departemen</option>
                                            {departments.map((x) => (
                                                <option key={x.id} value={x.id}>
                                                    {x.name}
                                                </option>
                                            ))}
                                        </select>
                                        <select
                                            name="position_id"
                                            className="h-9 rounded-md border bg-background px-3"
                                            required
                                        >
                                            <option value="">Posisi</option>
                                            {positions.map((x) => (
                                                <option key={x.id} value={x.id}>
                                                    {x.name}
                                                </option>
                                            ))}
                                        </select>
                                        <select
                                            name="location_id"
                                            className="h-9 rounded-md border bg-background px-3"
                                        >
                                            <option value="">Lokasi</option>
                                            {locations.map((x) => (
                                                <option key={x.id} value={x.id}>
                                                    {x.name}
                                                </option>
                                            ))}
                                        </select>
                                        <select
                                            name="employment_type_id"
                                            className="h-9 rounded-md border bg-background px-3"
                                        >
                                            <option value="">
                                                Tipe kepegawaian
                                            </option>
                                            {employmentTypes.map((item) => (
                                                <option
                                                    key={item.id}
                                                    value={item.id}
                                                >
                                                    {item.name}
                                                </option>
                                            ))}
                                        </select>
                                        <Input
                                            name="joined_at"
                                            type="date"
                                            required
                                        />
                                        <CurrencyInput
                                            name="basic_salary"
                                            placeholder="Gaji pokok"
                                            required
                                        />
                                        <select
                                            name="role"
                                            className="h-9 rounded-md border bg-background px-3"
                                        >
                                            <option value="employee">
                                                Employee
                                            </option>
                                            <option value="manager">
                                                Manager
                                            </option>
                                            <option value="hr_admin">
                                                HR Admin
                                            </option>
                                        </select>
                                        <Input
                                            name="password"
                                            type="password"
                                            autoComplete="new-password"
                                            placeholder="Password (opsional)"
                                        />
                                        <Input
                                            name="password_confirmation"
                                            type="password"
                                            autoComplete="new-password"
                                            placeholder="Ulangi password"
                                        />
                                        <Button disabled={processing}>
                                            Simpan
                                        </Button>
                                        {Object.keys(errors).length > 0 && (
                                            <p className="text-sm text-destructive">
                                                Periksa kembali data formulir.
                                            </p>
                                        )}
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                )}
                <Card>
                    <CardHeader className="flex flex-col gap-3 md:flex-row md:items-center">
                        <Input
                            defaultValue={filters.search ?? ''}
                            placeholder="Cari nama, email, atau nomor karyawan..."
                            className="md:max-w-xs"
                            onChange={(e) => onSearchChange(e.target.value)}
                        />
                        <select
                            value={filters.status}
                            className="h-9 rounded-md border bg-background px-3"
                            onChange={(e) =>
                                applyFilters({ status: e.target.value })
                            }
                        >
                            <option value="all">Semua status</option>
                            <option value="active">Aktif</option>
                            <option value="probation">Probation</option>
                            <option value="on_leave">Cuti panjang</option>
                            <option value="suspended">Diskors</option>
                            <option value="resigned">Resign</option>
                            <option value="terminated">Diberhentikan</option>
                        </select>
                    </CardHeader>
                    <CardContent className="overflow-x-auto p-0">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left">
                                    <th className="p-4">Nomor</th>
                                    <th>Nama</th>
                                    <th>Departemen</th>
                                    <th>Posisi</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {employees.data.map((e) => (
                                    <tr key={e.id} className="border-b">
                                        <td className="p-4 font-medium">
                                            {e.employee_number}
                                        </td>
                                        <td>
                                            <Link
                                                className="font-medium text-primary"
                                                href={`/employees/${e.id}`}
                                            >
                                                {e.user.name}
                                            </Link>
                                            <br />
                                            <span className="text-muted-foreground">
                                                {e.user.email}
                                            </span>
                                        </td>
                                        <td>{e.department.name}</td>
                                        <td>{e.position.name}</td>
                                        <td>
                                            {(() => {
                                                const meta =
                                                    STATUS_META[
                                                        e.employment_status ??
                                                            'active'
                                                    ] ?? STATUS_META.active;

                                                return (
                                                    <Badge
                                                        variant={meta.variant}
                                                    >
                                                        {meta.label}
                                                    </Badge>
                                                );
                                            })()}
                                        </td>
                                    </tr>
                                ))}
                                {employees.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="p-4 text-center text-muted-foreground"
                                        >
                                            Tidak ada karyawan yang cocok.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
