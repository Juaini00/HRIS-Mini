import { Form, Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
type Item = {
    id: number;
    employee_number: string;
    user: { name: string; email: string };
    department: { name: string };
    position: { name: string };
};
type Props = {
    employees: { data: Item[] };
    departments: Array<{ id: number; name: string }>;
    positions: Array<{ id: number; name: string }>;
    locations: Array<{ id: number; name: string }>;
    employmentTypes: Array<{ id: number; name: string }>;
    canCreate: boolean;
};
export default function Employees({
    employees,
    departments,
    positions,
    locations,
    employmentTypes,
    canCreate,
}: Props) {
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
                                        <Input
                                            name="basic_salary"
                                            type="number"
                                            min="0"
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
                    <CardContent className="overflow-x-auto p-0">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left">
                                    <th className="p-4">Nomor</th>
                                    <th>Nama</th>
                                    <th>Departemen</th>
                                    <th>Posisi</th>
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
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
