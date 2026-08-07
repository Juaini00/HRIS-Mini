import { Head } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
export default function Reports({
    payrollPeriods,
}: {
    payrollPeriods: Array<{ id: number; name: string }>;
}) {
    return (
        <>
            <Head title="Laporan" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold">
                        Laporan dan ekspor
                    </h1>
                    <p className="text-muted-foreground">
                        Unduh data HR yang telah difilter dalam format CSV.
                    </p>
                </div>
                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Data karyawan</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Button asChild>
                                <a href="/reports/employees.csv">
                                    <Download />
                                    Unduh CSV
                                </a>
                            </Button>
                        </CardContent>
                    </Card>
                    {['attendance', 'leave'].map((type) => (
                        <Card key={type}>
                            <CardHeader>
                                <CardTitle className="capitalize">
                                    {type}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form
                                    action={`/reports/${type}.csv`}
                                    className="grid gap-3 sm:grid-cols-3"
                                >
                                    <Input name="from" type="date" required />
                                    <Input name="to" type="date" required />
                                    <Button>
                                        <Download />
                                        Unduh
                                    </Button>
                                </form>
                            </CardContent>
                        </Card>
                    ))}
                    <Card>
                        <CardHeader>
                            <CardTitle>Payroll</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form
                                action="/reports/payroll.csv"
                                className="flex gap-3"
                            >
                                <select
                                    name="period_id"
                                    className="h-9 flex-1 rounded-md border bg-background px-3"
                                >
                                    {payrollPeriods.map((period) => (
                                        <option
                                            key={period.id}
                                            value={period.id}
                                        >
                                            {period.name}
                                        </option>
                                    ))}
                                </select>
                                <Button>
                                    <Download />
                                    Unduh
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
