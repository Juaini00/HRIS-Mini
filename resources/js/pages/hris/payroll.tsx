import { Form, Head, Link, router } from '@inertiajs/react';
import { CurrencyInput } from '@/components/currency-input';
import { InfoHint } from '@/components/info-hint';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

function formatComponentValue(calculationType: string, value: string): string {
    return calculationType === 'percentage'
        ? `${Number(value)}% dari gaji pokok`
        : `Rp ${Number(value).toLocaleString('id-ID')}`;
}
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
                            <CardContent className="grid gap-6">
                                <Form
                                    action="/salary-components"
                                    method="post"
                                    className="grid gap-4"
                                >
                                    <p className="text-sm font-medium">
                                        Tambah komponen baru
                                    </p>
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="component_name">
                                                Nama komponen
                                            </Label>
                                            <Input
                                                id="component_name"
                                                name="name"
                                                placeholder="Contoh: Tunjangan transport"
                                                required
                                            />
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label
                                                htmlFor="component_code"
                                                className="flex items-center gap-1.5"
                                            >
                                                Kode
                                                <InfoHint text="Kode singkat unik (opsional), misalnya TRANS atau BONUS. Boleh dikosongkan." />
                                            </Label>
                                            <Input
                                                id="component_code"
                                                name="code"
                                                placeholder="Opsional, mis. TRANS"
                                            />
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label
                                                htmlFor="component_type"
                                                className="flex items-center gap-1.5"
                                            >
                                                Jenis
                                                <InfoHint text="Pendapatan menambah gaji; potongan mengurangi gaji." />
                                            </Label>
                                            <select
                                                id="component_type"
                                                name="type"
                                                className="h-9 rounded-md border bg-background px-3 text-sm"
                                            >
                                                <option value="earning">
                                                    Pendapatan
                                                </option>
                                                <option value="deduction">
                                                    Potongan
                                                </option>
                                            </select>
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label
                                                htmlFor="component_calc"
                                                className="flex items-center gap-1.5"
                                            >
                                                Metode hitung
                                                <InfoHint text="Nominal tetap memakai angka rupiah apa adanya. Persentase menghitung nilai dari gaji pokok karyawan." />
                                            </Label>
                                            <select
                                                id="component_calc"
                                                name="calculation_type"
                                                className="h-9 rounded-md border bg-background px-3 text-sm"
                                            >
                                                <option value="fixed">
                                                    Nominal tetap (Rp)
                                                </option>
                                                <option value="percentage">
                                                    Persentase gaji (%)
                                                </option>
                                            </select>
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="component_value">
                                                Nilai
                                            </Label>
                                            <Input
                                                id="component_value"
                                                name="value"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                placeholder="Rupiah atau persen"
                                                required
                                            />
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label
                                                htmlFor="component_taxable"
                                                className="flex items-center gap-1.5"
                                            >
                                                Kena pajak
                                                <InfoHint text="Menandai apakah komponen ini termasuk objek pajak. Dipakai untuk pelaporan pajak." />
                                            </Label>
                                            <select
                                                id="component_taxable"
                                                name="is_taxable"
                                                className="h-9 rounded-md border bg-background px-3 text-sm"
                                                defaultValue="0"
                                            >
                                                <option value="0">Tidak</option>
                                                <option value="1">Ya</option>
                                            </select>
                                        </div>
                                    </div>
                                    <Button className="justify-self-start">
                                        Tambah komponen
                                    </Button>
                                </Form>

                                <div className="grid gap-3 border-t pt-4">
                                    <p className="text-sm font-medium">
                                        Tetapkan komponen ke karyawan
                                    </p>
                                    {components.length === 0 ? (
                                        <p className="text-sm text-muted-foreground">
                                            Belum ada komponen. Tambahkan di
                                            atas terlebih dahulu.
                                        </p>
                                    ) : null}
                                    {components.map((component) => (
                                        <div
                                            key={component.id}
                                            className="rounded-md border p-3"
                                        >
                                            <div className="mb-3 flex items-center gap-2">
                                                <span className="font-medium">
                                                    {component.name}
                                                </span>
                                                <Badge
                                                    variant={
                                                        component.type ===
                                                        'deduction'
                                                            ? 'destructive'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {component.type ===
                                                    'deduction'
                                                        ? 'Potongan'
                                                        : 'Pendapatan'}
                                                </Badge>
                                                <span className="text-sm text-muted-foreground">
                                                    {formatComponentValue(
                                                        component.calculation_type,
                                                        component.value,
                                                    )}
                                                </span>
                                            </div>
                                            <Form
                                                action={`/salary-components/${component.id}/assign`}
                                                method="post"
                                                className="grid items-end gap-3 sm:grid-cols-2"
                                            >
                                                <div className="grid min-w-0 gap-1.5">
                                                    <Label
                                                        htmlFor={`employee_${component.id}`}
                                                        className="text-xs"
                                                    >
                                                        Karyawan
                                                    </Label>
                                                    <select
                                                        id={`employee_${component.id}`}
                                                        name="employee_id"
                                                        className="h-9 w-full min-w-0 truncate rounded-md border bg-background px-3 text-sm"
                                                    >
                                                        {employees.map(
                                                            (employee) => (
                                                                <option
                                                                    key={
                                                                        employee.id
                                                                    }
                                                                    value={
                                                                        employee.id
                                                                    }
                                                                >
                                                                    {
                                                                        employee.employee_number
                                                                    }{' '}
                                                                    ·{' '}
                                                                    {
                                                                        employee
                                                                            .user
                                                                            .name
                                                                    }
                                                                </option>
                                                            ),
                                                        )}
                                                    </select>
                                                </div>
                                                <div className="grid gap-1.5">
                                                    <Label
                                                        htmlFor={`override_${component.id}`}
                                                        className="flex items-center gap-1.5 text-xs"
                                                    >
                                                        Nilai khusus
                                                        <InfoHint text="Nominal khusus untuk karyawan ini yang menimpa nilai default komponen. Kosongkan untuk memakai nilai default. Wajib diisi untuk komponen bernilai 0 seperti Bonus." />
                                                    </Label>
                                                    <CurrencyInput
                                                        id={`override_${component.id}`}
                                                        name="override_value"
                                                        placeholder="Opsional"
                                                    />
                                                </div>
                                                <div className="grid gap-1.5">
                                                    <Label
                                                        htmlFor={`effective_${component.id}`}
                                                        className="text-xs"
                                                    >
                                                        Berlaku sejak
                                                    </Label>
                                                    <Input
                                                        id={`effective_${component.id}`}
                                                        name="effective_from"
                                                        type="date"
                                                        required
                                                    />
                                                </div>
                                                <div className="grid gap-1.5">
                                                    <Label
                                                        htmlFor={`effective_to_${component.id}`}
                                                        className="flex items-center gap-1.5 text-xs"
                                                    >
                                                        Berlaku hingga
                                                        <InfoHint text="Tanggal komponen berhenti berlaku. Kosongkan bila berlaku selamanya." />
                                                    </Label>
                                                    <Input
                                                        id={`effective_to_${component.id}`}
                                                        name="effective_to"
                                                        type="date"
                                                    />
                                                </div>
                                                <Button
                                                    variant="outline"
                                                    className="w-full sm:col-span-2"
                                                >
                                                    Tetapkan
                                                </Button>
                                            </Form>
                                        </div>
                                    ))}
                                </div>
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
                                    <div className="flex flex-wrap gap-2">
                                        <Button
                                            variant="outline"
                                            onClick={() =>
                                                router.post(
                                                    `/payroll/${p.id}/recompute`,
                                                )
                                            }
                                        >
                                            Hitung ulang
                                        </Button>
                                        <Button
                                            onClick={() =>
                                                router.post(
                                                    `/payroll/${p.id}/publish`,
                                                )
                                            }
                                        >
                                            Publikasikan
                                        </Button>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </>
    );
}
