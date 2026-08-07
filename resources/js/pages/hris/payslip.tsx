import { Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
type Item = {
    id: number;
    name: string;
    type: 'earning' | 'deduction';
    amount: string;
};
type PayslipRecord = {
    id: number;
    basic_salary: string;
    earnings: string;
    deductions: string;
    net_salary: string;
    period: {
        name: string;
        starts_on: string;
        ends_on: string;
        status: string;
    };
    employee: {
        employee_number: string;
        user: { name: string };
        department: { name: string };
        position: { name: string };
    };
    items: Item[];
};
export default function Payslip({
    record,
    company,
}: {
    record: PayslipRecord;
    company: Record<string, string>;
}) {
    return (
        <>
            <Head title={`Payslip ${record.period.name}`} />
            <div className="mx-auto flex max-w-3xl flex-col gap-4 p-4 print:p-0">
                <div className="flex justify-end print:hidden">
                    <Button onClick={() => window.print()}>
                        Cetak payslip
                    </Button>
                </div>
                <Card>
                    <CardHeader className="border-b">
                        <CardTitle>
                            {company.company_name ?? 'NusaHR'} · Payslip{' '}
                            {record.period.name}
                        </CardTitle>
                        <p className="text-sm text-muted-foreground">
                            {record.period.starts_on} – {record.period.ends_on}
                        </p>
                    </CardHeader>
                    <CardContent className="grid gap-6 p-6">
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Karyawan
                                </p>
                                <p className="font-medium">
                                    {record.employee.user.name}
                                </p>
                                <p className="text-sm">
                                    {record.employee.employee_number}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Jabatan
                                </p>
                                <p>{record.employee.position.name}</p>
                                <p className="text-sm">
                                    {record.employee.department.name}
                                </p>
                            </div>
                        </div>
                        <div className="grid gap-2">
                            <div className="flex justify-between border-b py-2">
                                <span>Gaji pokok</span>
                                <strong>
                                    Rp{' '}
                                    {Number(record.basic_salary).toLocaleString(
                                        'id-ID',
                                    )}
                                </strong>
                            </div>
                            {record.items.map((item) => (
                                <div
                                    key={item.id}
                                    className="flex justify-between border-b py-2"
                                >
                                    <span>{item.name}</span>
                                    <span
                                        className={
                                            item.type === 'deduction'
                                                ? 'text-destructive'
                                                : 'text-emerald-600'
                                        }
                                    >
                                        {item.type === 'deduction' ? '-' : '+'}{' '}
                                        Rp{' '}
                                        {Number(item.amount).toLocaleString(
                                            'id-ID',
                                        )}
                                    </span>
                                </div>
                            ))}
                        </div>
                        <div className="flex justify-between rounded-lg bg-primary/5 p-4 text-lg">
                            <strong>Gaji bersih</strong>
                            <strong>
                                Rp{' '}
                                {Number(record.net_salary).toLocaleString(
                                    'id-ID',
                                )}
                            </strong>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
