import { Form, Head, router } from '@inertiajs/react';
import { Ban, Check, X } from 'lucide-react';
import { InfoHint } from '@/components/info-hint';
import { LeaveCalendar } from '@/components/leave-calendar';
import type {
    CalendarEntry,
    CalendarHoliday,
} from '@/components/leave-calendar';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
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
                            className="grid gap-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="leave_type_id">
                                                Jenis cuti
                                            </Label>
                                            <select
                                                id="leave_type_id"
                                                name="leave_type_id"
                                                className="h-9 rounded-md border bg-background px-3 text-sm"
                                                required
                                            >
                                                <option value="">
                                                    Pilih jenis cuti…
                                                </option>
                                                {types.map((x) => (
                                                    <option
                                                        key={x.id}
                                                        value={x.id}
                                                    >
                                                        {x.name}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="start_date">
                                                Tanggal mulai
                                            </Label>
                                            <Input
                                                id="start_date"
                                                name="start_date"
                                                type="date"
                                                required
                                            />
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="end_date">
                                                Tanggal selesai
                                            </Label>
                                            <Input
                                                id="end_date"
                                                name="end_date"
                                                type="date"
                                                required
                                            />
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label
                                                htmlFor="duration_type"
                                                className="flex items-center gap-1.5"
                                            >
                                                Durasi
                                                <InfoHint text="Pilih setengah hari hanya jika tanggal mulai dan selesai sama. Untuk rentang beberapa hari, gunakan sehari penuh." />
                                            </Label>
                                            <select
                                                id="duration_type"
                                                name="duration_type"
                                                className="h-9 rounded-md border bg-background px-3 text-sm"
                                            >
                                                <option value="full_day">
                                                    Sehari penuh
                                                </option>
                                                <option value="first_half">
                                                    Setengah hari (pagi)
                                                </option>
                                                <option value="second_half">
                                                    Setengah hari (siang)
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="reason">
                                                Alasan
                                            </Label>
                                            <Input
                                                id="reason"
                                                name="reason"
                                                placeholder="Contoh: acara keluarga"
                                                required
                                            />
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label
                                                htmlFor="attachment"
                                                className="flex items-center gap-1.5"
                                            >
                                                Lampiran
                                                <InfoHint text="Opsional. Lampirkan surat dokter atau dokumen pendukung (PDF/JPG/PNG)." />
                                            </Label>
                                            <Input
                                                id="attachment"
                                                name="attachment"
                                                type="file"
                                                accept=".pdf,.jpg,.jpeg,.png"
                                            />
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <Button disabled={processing}>
                                            Kirim pengajuan
                                        </Button>
                                        {Object.keys(errors).length > 0 && (
                                            <p className="text-sm text-destructive">
                                                {Object.values(errors)[0]}
                                            </p>
                                        )}
                                    </div>
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
                                    <th className="p-4 text-right">Aksi</th>
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
                                        <td className="p-4">
                                            <div className="flex items-center justify-end gap-1">
                                                {canReview &&
                                                    r.status === 'pending' && (
                                                        <>
                                                            <Tooltip>
                                                                <TooltipTrigger
                                                                    asChild
                                                                >
                                                                    <Button
                                                                        size="icon"
                                                                        className="size-8"
                                                                        aria-label="Setujui"
                                                                        onClick={() =>
                                                                            router.patch(
                                                                                `/leave/${r.id}/review`,
                                                                                {
                                                                                    status: 'approved',
                                                                                },
                                                                            )
                                                                        }
                                                                    >
                                                                        <Check className="size-4" />
                                                                    </Button>
                                                                </TooltipTrigger>
                                                                <TooltipContent>
                                                                    Setujui
                                                                </TooltipContent>
                                                            </Tooltip>
                                                            <Tooltip>
                                                                <TooltipTrigger
                                                                    asChild
                                                                >
                                                                    <Button
                                                                        size="icon"
                                                                        variant="destructive"
                                                                        className="size-8"
                                                                        aria-label="Tolak"
                                                                        onClick={() =>
                                                                            router.patch(
                                                                                `/leave/${r.id}/review`,
                                                                                {
                                                                                    status: 'rejected',
                                                                                },
                                                                            )
                                                                        }
                                                                    >
                                                                        <X className="size-4" />
                                                                    </Button>
                                                                </TooltipTrigger>
                                                                <TooltipContent>
                                                                    Tolak
                                                                </TooltipContent>
                                                            </Tooltip>
                                                        </>
                                                    )}
                                                {[
                                                    'pending',
                                                    'approved',
                                                ].includes(r.status) && (
                                                    <Tooltip>
                                                        <TooltipTrigger asChild>
                                                            <Button
                                                                size="icon"
                                                                variant="outline"
                                                                className="size-8"
                                                                aria-label="Batalkan"
                                                                onClick={() => {
                                                                    const reason =
                                                                        window.prompt(
                                                                            'Alasan pembatalan',
                                                                        );

                                                                    if (
                                                                        reason
                                                                    ) {
                                                                        router.patch(
                                                                            `/leave/${r.id}/cancel`,
                                                                            {
                                                                                reason,
                                                                            },
                                                                        );
                                                                    }
                                                                }}
                                                            >
                                                                <Ban className="size-4" />
                                                            </Button>
                                                        </TooltipTrigger>
                                                        <TooltipContent>
                                                            Batalkan
                                                        </TooltipContent>
                                                    </Tooltip>
                                                )}
                                                {r.status !== 'pending' &&
                                                    r.status !== 'approved' && (
                                                        <span className="text-muted-foreground">
                                                            —
                                                        </span>
                                                    )}
                                            </div>
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
