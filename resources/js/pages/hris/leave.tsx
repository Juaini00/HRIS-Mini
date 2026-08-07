import { Form, Head, router } from '@inertiajs/react';
import { LeaveCalendar } from '@/components/leave-calendar';
import type {
    CalendarEntry,
    CalendarHoliday,
} from '@/components/leave-calendar';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
type LeaveRequest = {
    id: number;
    start_date: string;
    end_date: string;
    status: string;
    employee?: { user?: { name: string } };
    leave_type?: { name: string };
};
type Props = {
    requests: { data: LeaveRequest[] };
    types: Array<{ id: number; name: string }>;
    balances: Array<{
        id: number;
        entitled: string;
        used: string;
        pending: string;
        leave_type?: { name: string };
    }>;
    calendar: CalendarEntry[];
    calendarScope: string;
    calendarScopes: string[];
    calendarMonth: string;
    holidays: CalendarHoliday[];
    departments: Array<{ id: number; name: string }>;
    filters: { department_id?: string; leave_type_id?: string };
    canReview: boolean;
};
export default function Leave({
    requests,
    types,
    balances,
    calendar,
    calendarScope,
    calendarScopes,
    calendarMonth,
    holidays,
    departments,
    filters,
    canReview,
}: Props) {
    return (
        <>
            <Head title="Cuti" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Manajemen cuti</h1>
                    <p className="text-muted-foreground">
                        Pengajuan, saldo, dan persetujuan cuti.
                    </p>
                </div>
                <div className="grid gap-4 md:grid-cols-3">
                    {balances.map((b) => (
                        <Card key={b.id}>
                            <CardContent className="p-5">
                                <p className="font-medium">
                                    {b.leave_type?.name}
                                </p>
                                <p className="text-2xl">
                                    {Number(b.entitled) -
                                        Number(b.used) -
                                        Number(b.pending)}{' '}
                                    hari
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    Terpakai {b.used}, menunggu {b.pending}
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </div>
                <LeaveCalendar
                    departments={departments}
                    entries={calendar}
                    filters={filters}
                    holidays={holidays}
                    leaveTypes={types}
                    month={calendarMonth}
                    scope={calendarScope}
                    scopes={calendarScopes}
                    showNames={calendarScope !== 'personal'}
                />
                <Card>
                    <CardHeader>
                        <CardTitle>Ajukan cuti</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Form
                            action="/leave"
                            method="post"
                            encType="multipart/form-data"
                            className="grid gap-3 md:grid-cols-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <select
                                        name="leave_type_id"
                                        className="h-9 rounded-md border bg-background px-3"
                                        required
                                    >
                                        <option value="">Jenis cuti</option>
                                        {types.map((x) => (
                                            <option key={x.id} value={x.id}>
                                                {x.name}
                                            </option>
                                        ))}
                                    </select>
                                    <Input
                                        name="start_date"
                                        type="date"
                                        required
                                    />
                                    <Input
                                        name="end_date"
                                        type="date"
                                        required
                                    />
                                    <select
                                        name="duration_type"
                                        className="h-9 rounded-md border bg-background px-3"
                                    >
                                        <option value="full_day">
                                            Sehari penuh
                                        </option>
                                        <option value="first_half">
                                            Setengah hari pagi
                                        </option>
                                        <option value="second_half">
                                            Setengah hari siang
                                        </option>
                                    </select>
                                    <Input
                                        name="reason"
                                        placeholder="Alasan"
                                        required
                                    />
                                    <Input
                                        name="attachment"
                                        type="file"
                                        accept=".pdf,.jpg,.jpeg,.png"
                                    />
                                    <Button disabled={processing}>
                                        Kirim pengajuan
                                    </Button>
                                    {Object.keys(errors).length > 0 && (
                                        <p className="text-sm text-destructive">
                                            {Object.values(errors)[0]}
                                        </p>
                                    )}
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="overflow-x-auto p-0">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left">
                                    <th className="p-4">Karyawan</th>
                                    <th>Jenis</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {requests.data.map((r) => (
                                    <tr key={r.id} className="border-b">
                                        <td className="p-4">
                                            {r.employee?.user?.name ?? 'Saya'}
                                        </td>
                                        <td>{r.leave_type?.name}</td>
                                        <td>
                                            {r.start_date} – {r.end_date}
                                        </td>
                                        <td className="capitalize">
                                            {r.status}
                                        </td>
                                        <td>
                                            {canReview &&
                                                r.status === 'pending' && (
                                                    <div className="flex gap-2">
                                                        <Button
                                                            size="sm"
                                                            onClick={() =>
                                                                router.patch(
                                                                    `/leave/${r.id}/review`,
                                                                    {
                                                                        status: 'approved',
                                                                    },
                                                                )
                                                            }
                                                        >
                                                            Setujui
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            variant="destructive"
                                                            onClick={() =>
                                                                router.patch(
                                                                    `/leave/${r.id}/review`,
                                                                    {
                                                                        status: 'rejected',
                                                                    },
                                                                )
                                                            }
                                                        >
                                                            Tolak
                                                        </Button>
                                                    </div>
                                                )}
                                            {['pending', 'approved'].includes(
                                                r.status,
                                            ) && (
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => {
                                                        const reason =
                                                            window.prompt(
                                                                'Alasan pembatalan',
                                                            );

                                                        if (reason) {
                                                            router.patch(
                                                                `/leave/${r.id}/cancel`,
                                                                { reason },
                                                            );
                                                        }
                                                    }}
                                                >
                                                    Batalkan
                                                </Button>
                                            )}
                                        </td>
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
