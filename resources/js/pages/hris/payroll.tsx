import { Form, Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
type PayrollRecord = {
    id: number;
    net_salary: string;
    employee?: { user?: { name: string } };
};
type PayrollPeriod = {
    id: number;
    name: string;
    status: string;
    starts_on: string;
    ends_on: string;
    records: PayrollRecord[];
};
export default function Payroll({
    periods,
    canManage,
    components,
    employees,
}: {
    periods: { data: PayrollPeriod[] };
    canManage: boolean;
    components: Array<{
        id: number;
        name: string;
        type: string;
        calculation_type: string;
        value: string;
    }>;
    employees: Array<{
        id: number;
        employee_number: string;
        user: { name: string };
    }>;
}) {
    return (
        <>
            <Head title="Payroll" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Payroll</h1>
                    <p className="text-muted-foreground">
                        Generate, tinjau, dan publikasikan penggajian.
                    </p>
                </div>
                {canManage && (
                    <div className="grid gap-4 lg:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>Buat periode</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <Form
                                    action="/payroll"
                                    method="post"
                                    className="grid gap-3 md:grid-cols-4"
                                >
                                    <Input
                                        name="name"
                                        placeholder="Nama periode"
                                        required
                                    />
                                    <Input
                                        name="starts_on"
                                        type="date"
                                        required
                                    />
                                    <Input
                                        name="ends_on"
                                        type="date"
                                        required
                                    />
                                    <Button>Generate payroll</Button>
                                </Form>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader>
                                <CardTitle>Komponen gaji</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-4">
                                <Form
                                    action="/salary-components"
                                    method="post"
                                    className="grid gap-2 sm:grid-cols-2"
                                >
                                    <Input
                                        name="name"
                                        placeholder="Nama komponen"
                                        required
                                    />
                                    <select
                                        name="type"
                                        className="h-9 rounded-md border bg-background px-3"
                                    >
                                        <option value="earning">
                                            Pendapatan
                                        </option>
                                        <option value="deduction">
                                            Potongan
                                        </option>
                                    </select>
                                    <select
                                        name="calculation_type"
                                        className="h-9 rounded-md border bg-background px-3"
                                    >
                                        <option value="fixed">
                                            Nominal tetap
                                        </option>
                                        <option value="percentage">
                                            Persentase gaji
                                        </option>
                                    </select>
                                    <Input
                                        name="value"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="Nilai"
                                        required
                                    />
                                    <Button>Tambah komponen</Button>
                                </Form>
                                {components.map((component) => (
                                    <Form
                                        key={component.id}
                                        action={`/salary-components/${component.id}/assign`}
                                        method="post"
                                        className="grid gap-2 border-t pt-3 sm:grid-cols-3"
                                    >
                                        <div className="text-sm">
                                            <p className="font-medium">
                                                {component.name}
                                            </p>
                                            <p className="text-muted-foreground">
                                                {component.calculation_type} ·{' '}
                                                {component.value}
                                            </p>
                                        </div>
                                        <select
                                            name="employee_id"
                                            className="h-9 rounded-md border bg-background px-3"
                                        >
                                            {employees.map((employee) => (
                                                <option
                                                    key={employee.id}
                                                    value={employee.id}
                                                >
                                                    {employee.employee_number} ·{' '}
                                                    {employee.user.name}
                                                </option>
                                            ))}
                                        </select>
                                        <Input
                                            name="effective_from"
                                            type="date"
                                            required
                                        />
                                        <Button size="sm" variant="outline">
                                            Tetapkan
                                        </Button>
                                    </Form>
                                ))}
                            </CardContent>
                        </Card>
                    </div>
                )}
                <div className="grid gap-4 lg:grid-cols-2">
                    {periods.data.map((p) => (
                        <Card key={p.id}>
                            <CardHeader>
                                <CardTitle className="flex justify-between">
                                    {p.name}
                                    <span className="text-sm font-normal capitalize">
                                        {p.status}
                                    </span>
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-3">
                                <p className="text-sm text-muted-foreground">
                                    {p.starts_on} – {p.ends_on}
                                </p>
                                {p.records.map((r) => (
                                    <div
                                        key={r.id}
                                        className="flex justify-between border-t pt-2"
                                    >
                                        <Link
                                            className="font-medium text-primary"
                                            href={`/payslips/${r.id}`}
                                        >
                                            {r.employee?.user?.name ??
                                                'Payslip saya'}
                                        </Link>
                                        <strong>
                                            Rp{' '}
                                            {Number(
                                                r.net_salary,
                                            ).toLocaleString('id-ID')}
                                        </strong>
                                    </div>
                                ))}
                                {canManage && p.status === 'draft' && (
                                    <Button
                                        onClick={() =>
                                            router.post(
                                                `/payroll/${p.id}/publish`,
                                            )
                                        }
                                    >
                                        Publikasikan
                                    </Button>
                                )}
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </>
    );
}
