import { Head } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { useId } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Report = {
    key: string;
    label: string;
    group: string;
    team: boolean;
    filters: string[];
};

type Props = {
    reports: Report[];
    scope: 'company' | 'team';
    payrollPeriods: { id: number; name: string }[];
};

const FILTER_LABELS: Record<string, string> = {
    from: 'Dari tanggal',
    to: 'Sampai tanggal',
    date: 'Tanggal',
    year: 'Tahun',
    days: 'Rentang hari',
    period_id: 'Periode payroll',
};

/** One-line purpose for each report, keyed by report key. */
const REPORT_DESCRIPTIONS: Record<string, string> = {
    'employee-directory':
        'Daftar lengkap karyawan beserta data jabatan dan status kepegawaian.',
    'headcount-by-department':
        'Jumlah karyawan aktif per departemen untuk melihat sebaran tenaga kerja.',
    'contract-expiration':
        'Karyawan yang kontraknya akan berakhir dalam rentang hari yang dipilih.',
    'probation-expiration':
        'Karyawan yang masa percobaannya akan berakhir dalam rentang hari yang dipilih.',
    'daily-attendance':
        'Rekap kehadiran seluruh karyawan pada satu tanggal tertentu.',
    'monthly-attendance':
        'Ringkasan kehadiran per karyawan sepanjang rentang tanggal.',
    'late-attendance':
        'Daftar keterlambatan beserta jumlah menit terlambat pada rentang tanggal.',
    absence: 'Karyawan yang tidak hadir tanpa keterangan pada rentang tanggal.',
    'leave-requests':
        'Seluruh pengajuan cuti beserta statusnya pada rentang tanggal.',
    'leave-usage':
        'Total pemakaian cuti dikelompokkan per jenis cuti dalam satu tahun.',
    'leave-balances':
        'Sisa saldo cuti setiap karyawan untuk tahun yang dipilih.',
    'payroll-summary':
        'Ringkasan total penggajian untuk satu periode payroll.',
    'payroll-details':
        'Rincian gaji per karyawan (pendapatan, potongan, gaji bersih) untuk satu periode.',
    'announcement-readership':
        'Tingkat keterbacaan tiap pengumuman: siapa yang sudah dan belum membaca.',
    'audit-activity':
        'Jejak aktivitas sistem pada rentang tanggal untuk keperluan audit.',
};

const today = new Date().toISOString().slice(0, 10);
const monthStart = `${today.slice(0, 7)}-01`;
const currentYear = new Date().getFullYear();

function FilterField({
    filter,
    payrollPeriods,
}: {
    filter: string;
    payrollPeriods: Props['payrollPeriods'];
}) {
    const id = `${useId()}-${filter}`;

    return (
        <div className="grid gap-1.5">
            <Label className="text-xs" htmlFor={id}>
                {FILTER_LABELS[filter] ?? filter}
            </Label>
            {filter === 'period_id' ? (
                <select
                    className="h-9 rounded-md border bg-background px-3 text-sm"
                    id={id}
                    name={filter}
                    required
                >
                    {payrollPeriods.map((period) => (
                        <option key={period.id} value={period.id}>
                            {period.name}
                        </option>
                    ))}
                </select>
            ) : filter === 'year' ? (
                <Input
                    defaultValue={currentYear}
                    id={id}
                    max={2100}
                    min={2000}
                    name={filter}
                    required
                    type="number"
                />
            ) : filter === 'days' ? (
                <Input
                    defaultValue={90}
                    id={id}
                    max={365}
                    min={1}
                    name={filter}
                    type="number"
                />
            ) : (
                <Input
                    defaultValue={filter === 'from' ? monthStart : today}
                    id={id}
                    name={filter}
                    required
                    type="date"
                />
            )}
        </div>
    );
}

export default function Reports({ reports, scope, payrollPeriods }: Props) {
    const groups = reports.reduce<Record<string, Report[]>>((acc, report) => {
        (acc[report.group] ??= []).push(report);

        return acc;
    }, {});

    return (
        <>
            <Head title="Laporan" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold">
                        Laporan dan ekspor
                    </h1>
                    <p className="text-muted-foreground">
                        Unduh data HR dalam format CSV. Ekspor mengikuti izin
                        dan filter yang sama dengan tampilan layar
                        {scope === 'team'
                            ? ', dan dibatasi pada anggota tim Anda.'
                            : '.'}
                    </p>
                </div>

                {Object.entries(groups).map(([group, items]) => (
                    <section className="flex flex-col gap-3" key={group}>
                        <h2 className="text-sm font-medium tracking-wide text-muted-foreground uppercase">
                            {group}
                        </h2>
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            {items.map((report) => (
                                <Card
                                    className="flex flex-col"
                                    key={report.key}
                                >
                                    <CardHeader className="space-y-1.5">
                                        <div className="flex items-start justify-between gap-2">
                                            <CardTitle className="text-base">
                                                {report.label}
                                            </CardTitle>
                                            {scope === 'company' &&
                                            report.team ? (
                                                <Badge variant="secondary">
                                                    Tim
                                                </Badge>
                                            ) : null}
                                        </div>
                                        {REPORT_DESCRIPTIONS[report.key] ? (
                                            <p className="text-sm text-muted-foreground">
                                                {REPORT_DESCRIPTIONS[report.key]}
                                            </p>
                                        ) : null}
                                    </CardHeader>
                                    <CardContent className="mt-auto">
                                        <form
                                            action={`/reports/${report.key}/export`}
                                            className="grid gap-3"
                                            method="get"
                                        >
                                            {report.filters.length > 0 ? (
                                                <div className="grid gap-3 sm:grid-cols-2">
                                                    {report.filters.map(
                                                        (filter) => (
                                                            <FilterField
                                                                filter={filter}
                                                                key={filter}
                                                                payrollPeriods={
                                                                    payrollPeriods
                                                                }
                                                            />
                                                        ),
                                                    )}
                                                </div>
                                            ) : null}
                                            <Button
                                                className="w-full sm:w-auto"
                                                type="submit"
                                            >
                                                <Download />
                                                Unduh CSV
                                            </Button>
                                        </form>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </section>
                ))}
            </div>
        </>
    );
}
