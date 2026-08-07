import { Head, Link } from '@inertiajs/react';
import {
    Building2,
    CalendarClock,
    CalendarDays,
    CircleAlert,
    Clock3,
    FileText,
    Pin,
    UserCheck,
    Users,
} from 'lucide-react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    Legend,
    Line,
    LineChart,
    Pie,
    PieChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';

type NamedRow = { label: string; value: number; color?: string };

type TrendPoint = {
    date: string;
    present: number;
    late: number;
    absent: number;
    leave: number;
};

type PersonRef = {
    id: number;
    employee_number: string;
    user?: { name: string };
};

type LeaveRow = {
    id: number;
    start_date: string;
    end_date: string;
    days: string;
    employee?: PersonRef;
    leave_type?: { name: string; color?: string };
};

type Props = {
    role: string;
    canSeePayrollValue: boolean;
    stats?: Record<string, number | Record<string, unknown>>;
    charts?: {
        byDepartment: NamedRow[];
        byEmploymentType: NamedRow[];
        attendanceTrend: TrendPoint[];
        leaveUsageByType: NamedRow[];
    };
    lists?: {
        pendingApprovals?: LeaveRow[];
        recentHires?: (PersonRef & {
            joined_at: string;
            position?: { name: string };
        })[];
        birthdays?: (PersonRef & { date_of_birth: string })[];
        contractExpirations?: (PersonRef & { contract_ends_on: string })[];
        teamOnLeave?: LeaveRow[];
    };
    personal?: {
        employeeNumber: string;
        today?: {
            status: string;
            checked_in_at?: string;
            checked_out_at?: string;
        };
        monthlySummary: Record<string, number>;
        leaveBalances: {
            id: number;
            remaining: number;
            entitled: string;
            leave_type?: { name: string; color?: string };
        }[];
        pendingLeave: number;
        upcomingLeave: LeaveRow[];
        latestPayslip?: {
            id: number;
            net_salary: string;
            period?: { name: string };
        };
    } | null;
    announcements: {
        id: number;
        title: string;
        summary?: string;
        is_pinned: boolean;
        published_at?: string;
    }[];
};

/** Chart palette kept to a small, colour-blind-safe set reused across every figure. */
const SERIES = {
    present: '#2563eb',
    late: '#f59e0b',
    absent: '#dc2626',
    leave: '#0f766e',
};

const CATEGORICAL = [
    '#2563eb',
    '#0f766e',
    '#f59e0b',
    '#7c3aed',
    '#db2777',
    '#0891b2',
    '#65a30d',
    '#dc2626',
];

const currency = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

function StatCard({
    label,
    value,
    icon: Icon,
    hint,
}: {
    label: string;
    value: string | number;
    icon: typeof Users;
    hint?: string;
}) {
    return (
        <Card>
            <CardContent className="flex items-start justify-between gap-3 pt-6">
                <div className="min-w-0">
                    <p className="truncate text-sm text-muted-foreground">
                        {label}
                    </p>
                    <p className="mt-1 text-2xl font-semibold tabular-nums">
                        {value}
                    </p>
                    {hint ? (
                        <p className="mt-1 truncate text-xs text-muted-foreground">
                            {hint}
                        </p>
                    ) : null}
                </div>
                <Icon
                    aria-hidden
                    className="size-5 shrink-0 text-muted-foreground"
                />
            </CardContent>
        </Card>
    );
}

function ChartCard({
    title,
    children,
    empty,
}: {
    title: string;
    children: React.ReactNode;
    empty: boolean;
}) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">{title}</CardTitle>
            </CardHeader>
            <CardContent>
                {empty ? (
                    <p className="py-10 text-center text-sm text-muted-foreground">
                        Belum ada data untuk ditampilkan.
                    </p>
                ) : (
                    <div className="h-64 w-full">
                        <ResponsiveContainer height="100%" width="100%">
                            {children as React.ReactElement}
                        </ResponsiveContainer>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

function ListCard({
    title,
    children,
    emptyLabel,
    href,
}: {
    title: string;
    children: React.ReactNode;
    emptyLabel: string;
    href?: string;
}) {
    const hasChildren = Array.isArray(children)
        ? children.length > 0
        : Boolean(children);

    return (
        <Card>
            <CardHeader className="flex-row items-center justify-between space-y-0">
                <CardTitle className="text-base">{title}</CardTitle>
                {href ? (
                    <Link
                        className="text-sm font-medium text-primary"
                        href={href}
                    >
                        Lihat semua
                    </Link>
                ) : null}
            </CardHeader>
            <CardContent className="space-y-3">
                {hasChildren ? (
                    children
                ) : (
                    <p className="text-sm text-muted-foreground">
                        {emptyLabel}
                    </p>
                )}
            </CardContent>
        </Card>
    );
}

function personName(person?: PersonRef) {
    return person?.user?.name ?? person?.employee_number ?? '—';
}

export default function Dashboard({
    role,
    canSeePayrollValue,
    stats,
    charts,
    lists,
    personal,
    announcements,
}: Props) {
    const payroll = stats?.payroll as
        | {
              name?: string;
              status?: string;
              records?: number;
              totalNet?: number | null;
          }
        | undefined;

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold">
                        Selamat datang di NusaHR
                    </h1>
                    <p className="text-muted-foreground">
                        Ringkasan operasional sumber daya manusia hari ini.
                    </p>
                </div>

                {/* HR and Super Admin: company-wide numbers. */}
                {stats && role !== 'manager' ? (
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <StatCard
                            hint={`${stats.totalEmployees as number} total tercatat`}
                            icon={Users}
                            label="Karyawan aktif"
                            value={stats.activeEmployees as number}
                        />
                        <StatCard
                            hint={`${stats.lateToday as number} terlambat`}
                            icon={UserCheck}
                            label="Hadir hari ini"
                            value={stats.presentToday as number}
                        />
                        <StatCard
                            hint={`${stats.onLeaveToday as number} sedang cuti`}
                            icon={CircleAlert}
                            label="Absen hari ini"
                            value={stats.absentToday as number}
                        />
                        <StatCard
                            icon={CalendarDays}
                            label="Cuti menunggu persetujuan"
                            value={stats.pendingLeave as number}
                        />
                        <StatCard
                            icon={CalendarClock}
                            label="Kontrak berakhir ≤60 hari"
                            value={stats.contractsEndingSoon as number}
                        />
                        <StatCard
                            icon={Clock3}
                            label="Probation berakhir ≤30 hari"
                            value={stats.probationEndingSoon as number}
                        />
                        <StatCard
                            icon={Building2}
                            label="Karyawan baru bulan ini"
                            value={stats.newHiresThisMonth as number}
                        />
                        <StatCard
                            hint={payroll?.name}
                            icon={FileText}
                            label="Payroll bulan berjalan"
                            value={
                                canSeePayrollValue && payroll?.totalNet != null
                                    ? currency.format(payroll.totalNet)
                                    : (payroll?.status ?? '—')
                            }
                        />
                    </div>
                ) : null}

                {/* Managers get team-scoped numbers and never see payroll value. */}
                {stats && role === 'manager' ? (
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                        <StatCard
                            icon={Users}
                            label="Bawahan langsung"
                            value={stats.directReports as number}
                        />
                        <StatCard
                            icon={Users}
                            label="Total tim"
                            value={stats.teamSize as number}
                        />
                        <StatCard
                            icon={UserCheck}
                            label="Hadir hari ini"
                            value={stats.presentToday as number}
                        />
                        <StatCard
                            icon={CircleAlert}
                            label="Absen hari ini"
                            value={stats.absentToday as number}
                        />
                        <StatCard
                            icon={CalendarDays}
                            label="Menunggu persetujuan"
                            value={stats.pendingLeave as number}
                        />
                    </div>
                ) : null}

                {charts ? (
                    <div className="grid gap-4 lg:grid-cols-2">
                        <ChartCard
                            empty={charts.attendanceTrend.length === 0}
                            title="Tren kehadiran 30 hari"
                        >
                            <LineChart data={charts.attendanceTrend}>
                                <CartesianGrid
                                    strokeDasharray="3 3"
                                    vertical={false}
                                />
                                <XAxis
                                    dataKey="date"
                                    fontSize={12}
                                    tickFormatter={(v: string) => v.slice(5)}
                                />
                                <YAxis
                                    allowDecimals={false}
                                    fontSize={12}
                                    width={32}
                                />
                                <Tooltip />
                                <Legend />
                                <Line
                                    dataKey="present"
                                    name="Hadir"
                                    stroke={SERIES.present}
                                    strokeWidth={2}
                                    type="monotone"
                                />
                                <Line
                                    dataKey="late"
                                    name="Terlambat"
                                    stroke={SERIES.late}
                                    strokeWidth={2}
                                    type="monotone"
                                />
                                <Line
                                    dataKey="absent"
                                    name="Absen"
                                    stroke={SERIES.absent}
                                    strokeWidth={2}
                                    type="monotone"
                                />
                                <Line
                                    dataKey="leave"
                                    name="Cuti"
                                    stroke={SERIES.leave}
                                    strokeWidth={2}
                                    type="monotone"
                                />
                            </LineChart>
                        </ChartCard>

                        <ChartCard
                            empty={charts.byDepartment.length === 0}
                            title="Karyawan per departemen"
                        >
                            <BarChart
                                data={charts.byDepartment}
                                layout="vertical"
                            >
                                <CartesianGrid
                                    horizontal={false}
                                    strokeDasharray="3 3"
                                />
                                <XAxis
                                    allowDecimals={false}
                                    fontSize={12}
                                    type="number"
                                />
                                <YAxis
                                    dataKey="label"
                                    fontSize={12}
                                    type="category"
                                    width={120}
                                />
                                <Tooltip />
                                <Bar
                                    dataKey="value"
                                    fill={SERIES.present}
                                    name="Karyawan"
                                    radius={[0, 4, 4, 0]}
                                />
                            </BarChart>
                        </ChartCard>

                        <ChartCard
                            empty={charts.byEmploymentType.length === 0}
                            title="Karyawan per tipe kepegawaian"
                        >
                            <PieChart>
                                <Tooltip />
                                <Legend />
                                <Pie
                                    data={charts.byEmploymentType}
                                    dataKey="value"
                                    innerRadius={50}
                                    nameKey="label"
                                    outerRadius={90}
                                >
                                    {charts.byEmploymentType.map(
                                        (row, index) => (
                                            <Cell
                                                key={row.label}
                                                fill={
                                                    CATEGORICAL[
                                                        index %
                                                            CATEGORICAL.length
                                                    ]
                                                }
                                            />
                                        ),
                                    )}
                                </Pie>
                            </PieChart>
                        </ChartCard>

                        <ChartCard
                            empty={charts.leaveUsageByType.length === 0}
                            title="Penggunaan cuti per jenis"
                        >
                            <BarChart data={charts.leaveUsageByType}>
                                <CartesianGrid
                                    strokeDasharray="3 3"
                                    vertical={false}
                                />
                                <XAxis dataKey="label" fontSize={12} />
                                <YAxis
                                    allowDecimals={false}
                                    fontSize={12}
                                    width={32}
                                />
                                <Tooltip />
                                <Bar
                                    dataKey="value"
                                    name="Hari"
                                    radius={[4, 4, 0, 0]}
                                >
                                    {charts.leaveUsageByType.map(
                                        (row, index) => (
                                            <Cell
                                                key={row.label}
                                                fill={
                                                    row.color ??
                                                    CATEGORICAL[
                                                        index %
                                                            CATEGORICAL.length
                                                    ]
                                                }
                                            />
                                        ),
                                    )}
                                </Bar>
                            </BarChart>
                        </ChartCard>
                    </div>
                ) : null}

                <div className="grid gap-4 lg:grid-cols-2">
                    {personal ? (
                        <ListCard
                            emptyLabel="Belum ada saldo cuti."
                            title={`Saldo cuti — ${personal.employeeNumber}`}
                        >
                            {personal.leaveBalances.map((balance) => (
                                <div
                                    className="flex items-center justify-between gap-3"
                                    key={balance.id}
                                >
                                    <span className="flex min-w-0 items-center gap-2">
                                        <span
                                            aria-hidden
                                            className="size-2.5 shrink-0 rounded-full"
                                            style={{
                                                background:
                                                    balance.leave_type?.color ??
                                                    SERIES.present,
                                            }}
                                        />
                                        <span className="truncate text-sm">
                                            {balance.leave_type?.name}
                                        </span>
                                    </span>
                                    <span className="text-sm tabular-nums">
                                        {balance.remaining} /{' '}
                                        {Number(balance.entitled)} hari
                                    </span>
                                </div>
                            ))}
                        </ListCard>
                    ) : null}

                    {lists?.pendingApprovals ? (
                        <ListCard
                            emptyLabel="Tidak ada permintaan menunggu."
                            href="/leave"
                            title="Cuti menunggu persetujuan"
                        >
                            {lists.pendingApprovals.map((row) => (
                                <div
                                    className="flex items-center justify-between gap-3"
                                    key={row.id}
                                >
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium">
                                            {personName(row.employee)}
                                        </p>
                                        <p className="truncate text-xs text-muted-foreground">
                                            {row.leave_type?.name} ·{' '}
                                            {row.start_date} → {row.end_date}
                                        </p>
                                    </div>
                                    <Badge variant="secondary">
                                        {Number(row.days)} hari
                                    </Badge>
                                </div>
                            ))}
                        </ListCard>
                    ) : null}

                    {lists?.recentHires ? (
                        <ListCard
                            emptyLabel="Belum ada perekrutan baru."
                            href="/employees"
                            title="Karyawan baru"
                        >
                            {lists.recentHires.map((row) => (
                                <div
                                    className="flex items-center justify-between gap-3"
                                    key={row.id}
                                >
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium">
                                            {personName(row)}
                                        </p>
                                        <p className="truncate text-xs text-muted-foreground">
                                            {row.position?.name}
                                        </p>
                                    </div>
                                    <span className="text-xs text-muted-foreground">
                                        {row.joined_at}
                                    </span>
                                </div>
                            ))}
                        </ListCard>
                    ) : null}

                    {lists?.birthdays ? (
                        <ListCard
                            emptyLabel="Tidak ada ulang tahun bulan ini."
                            title="Ulang tahun bulan ini"
                        >
                            {lists.birthdays.map((row) => (
                                <div
                                    className="flex items-center justify-between gap-3"
                                    key={row.id}
                                >
                                    <p className="truncate text-sm">
                                        {personName(row)}
                                    </p>
                                    <span className="text-xs text-muted-foreground">
                                        {row.date_of_birth?.slice(5)}
                                    </span>
                                </div>
                            ))}
                        </ListCard>
                    ) : null}

                    {lists?.teamOnLeave ? (
                        <ListCard
                            emptyLabel="Tidak ada cuti mendatang."
                            href="/leave"
                            title="Cuti tim mendatang"
                        >
                            {lists.teamOnLeave.map((row) => (
                                <div
                                    className="flex items-center justify-between gap-3"
                                    key={row.id}
                                >
                                    <p className="truncate text-sm">
                                        {personName(row.employee)}
                                    </p>
                                    <span className="text-xs text-muted-foreground">
                                        {row.start_date} → {row.end_date}
                                    </span>
                                </div>
                            ))}
                        </ListCard>
                    ) : null}

                    <ListCard
                        emptyLabel="Belum ada pengumuman."
                        href="/announcements"
                        title="Pengumuman terbaru"
                    >
                        {announcements.map((item) => (
                            <div
                                className="border-b pb-3 last:border-0"
                                key={item.id}
                            >
                                <p className="flex items-center gap-2 font-medium">
                                    {item.is_pinned ? (
                                        <Pin
                                            aria-label="Disematkan"
                                            className="size-3.5 text-primary"
                                        />
                                    ) : null}
                                    <span className="truncate">
                                        {item.title}
                                    </span>
                                </p>
                                {item.summary ? (
                                    <p className="line-clamp-2 text-sm text-muted-foreground">
                                        {item.summary}
                                    </p>
                                ) : null}
                            </div>
                        ))}
                    </ListCard>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = { breadcrumbs: [{ title: 'Dashboard', href: dashboard() }] };
