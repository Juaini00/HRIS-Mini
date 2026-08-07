import { Head, router } from '@inertiajs/react';
import { Check, MessageSquareWarning, PenLine, X } from 'lucide-react';
import { useState } from 'react';
import { InfoHint } from '@/components/info-hint';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type AttendanceRow = {
    id: number;
    date: string;
    checked_in_at?: string;
    checked_out_at?: string;
    worked_minutes: number;
    late_minutes: number;
    status: string;
    employee?: { user: { name: string } };
    corrections?: Array<{ id: number; status: string }>;
};

type Correction = {
    id: number;
    status: string;
    reason: string;
    review_notes?: string;
    reviewed_at?: string;
    new_values?: Record<string, string>;
    requester?: { name: string };
    attendance?: {
        date: string;
        status: string;
        employee?: { employee_number: string; user?: { name: string } };
    };
};

type Props = {
    attendances: { data: AttendanceRow[] };
    today?: AttendanceRow;
    checkInBlockedReason?: string | null;
    canCorrect: boolean;
    corrections: Correction[];
};

const STATUS_VARIANT: Record<string, 'default' | 'secondary' | 'destructive'> =
    {
        approved: 'default',
        pending: 'secondary',
        rejected: 'destructive',
    };

function toLocalInput(value?: string) {
    return value ? value.slice(0, 16).replace(' ', 'T') : '';
}

export default function Attendance({
    attendances,
    today,
    checkInBlockedReason,
    canCorrect,
    corrections,
}: Props) {
    const alreadyCheckedIn = Boolean(today?.checked_in_at);
    const checkInDisabled = alreadyCheckedIn || Boolean(checkInBlockedReason);
    const checkInHint = alreadyCheckedIn
        ? 'Anda sudah check-in hari ini.'
        : checkInBlockedReason;
    const [correcting, setCorrecting] = useState<AttendanceRow | null>(null);
    const [requesting, setRequesting] = useState<AttendanceRow | null>(null);
    const [reviewing, setReviewing] = useState<Correction | null>(null);

    const [reason, setReason] = useState('');
    const [checkedIn, setCheckedIn] = useState('');
    const [checkedOut, setCheckedOut] = useState('');
    const [reviewNotes, setReviewNotes] = useState('');

    const openCorrect = (row: AttendanceRow) => {
        setReason('');
        setCheckedIn(toLocalInput(row.checked_in_at));
        setCheckedOut(toLocalInput(row.checked_out_at));
        setCorrecting(row);
    };

    const openRequest = (row: AttendanceRow) => {
        setReason('');
        setCheckedIn(toLocalInput(row.checked_in_at));
        setCheckedOut(toLocalInput(row.checked_out_at));
        setRequesting(row);
    };

    const submitCorrection = () => {
        if (!correcting) {
            return;
        }

        router.patch(
            `/attendance/${correcting.id}/correct`,
            {
                checked_in_at: checkedIn || null,
                checked_out_at: checkedOut || null,
                status: correcting.status,
                correction_reason: reason,
            },
            { preserveScroll: true, onSuccess: () => setCorrecting(null) },
        );
    };

    const submitRequest = () => {
        if (!requesting) {
            return;
        }

        router.post(
            `/attendance/${requesting.id}/correction-requests`,
            {
                reason,
                checked_in_at: checkedIn || null,
                checked_out_at: checkedOut || null,
            },
            { preserveScroll: true, onSuccess: () => setRequesting(null) },
        );
    };

    const review = (decision: 'approve' | 'reject') => {
        if (!reviewing) {
            return;
        }

        router.patch(
            `/attendance-corrections/${reviewing.id}`,
            { decision, review_notes: reviewNotes || null },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setReviewing(null);
                    setReviewNotes('');
                },
            },
        );
    };

    const pendingCount = corrections.filter(
        (c) => c.status === 'pending',
    ).length;

    return (
        <>
            <Head title="Kehadiran" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Kehadiran</h1>
                    <p className="text-muted-foreground">
                        Catatan kehadiran, koreksi, dan permintaan perbaikan.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-1.5 text-base">
                            Hari ini
                            <InfoHint text="Check-in hanya tersedia satu kali pada hari kerja (Senin–Jumat), di luar hari libur, dan saat Anda tidak sedang cuti. Keterlambatan dihitung otomatis dari jam masuk kantor." />
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-wrap items-center gap-4">
                        <div>
                            <p className="text-xs text-muted-foreground">
                                Status
                            </p>
                            <p className="text-lg font-semibold capitalize">
                                {today?.status ?? 'Belum tercatat'}
                            </p>
                        </div>
                        <div>
                            <p className="text-xs text-muted-foreground">
                                Masuk
                            </p>
                            <p className="text-lg tabular-nums">
                                {today?.checked_in_at?.slice(11, 16) ?? '—'}
                            </p>
                        </div>
                        <div>
                            <p className="text-xs text-muted-foreground">
                                Pulang
                            </p>
                            <p className="text-lg tabular-nums">
                                {today?.checked_out_at?.slice(11, 16) ?? '—'}
                            </p>
                        </div>
                        <div className="ml-auto flex flex-col items-end gap-1.5">
                            <div className="flex gap-2">
                                <Button
                                    disabled={checkInDisabled}
                                    onClick={() =>
                                        router.post(
                                            '/attendance/check-in',
                                            {},
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    Check in
                                </Button>
                                <Button
                                    disabled={
                                        !today?.checked_in_at ||
                                        Boolean(today?.checked_out_at)
                                    }
                                    onClick={() =>
                                        router.post(
                                            '/attendance/check-out',
                                            {},
                                            { preserveScroll: true },
                                        )
                                    }
                                    variant="outline"
                                >
                                    Check out
                                </Button>
                            </div>
                            {checkInDisabled && checkInHint ? (
                                <p className="text-xs text-muted-foreground">
                                    {checkInHint}
                                </p>
                            ) : null}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex-row items-center justify-between space-y-0">
                        <CardTitle className="text-base">
                            {canCorrect
                                ? 'Permintaan koreksi'
                                : 'Permintaan koreksi saya'}
                        </CardTitle>
                        {pendingCount > 0 ? (
                            <Badge variant="secondary">
                                {pendingCount} menunggu
                            </Badge>
                        ) : null}
                    </CardHeader>
                    <CardContent className="flex flex-col gap-2">
                        {corrections.map((correction) => (
                            <div
                                className="flex flex-wrap items-start justify-between gap-3 rounded-md border p-3"
                                key={correction.id}
                            >
                                <div className="min-w-0">
                                    <p className="text-sm font-medium">
                                        {correction.attendance?.date}
                                        {canCorrect &&
                                        correction.attendance?.employee?.user
                                            ? ` · ${correction.attendance.employee.user.name}`
                                            : ''}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {correction.reason}
                                    </p>
                                    {correction.review_notes ? (
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            Catatan HR:{' '}
                                            {correction.review_notes}
                                        </p>
                                    ) : null}
                                </div>
                                <div className="flex items-center gap-2">
                                    <Badge
                                        variant={
                                            STATUS_VARIANT[correction.status] ??
                                            'secondary'
                                        }
                                    >
                                        {correction.status}
                                    </Badge>
                                    {canCorrect &&
                                    correction.status === 'pending' ? (
                                        <Button
                                            onClick={() =>
                                                setReviewing(correction)
                                            }
                                            size="sm"
                                            variant="outline"
                                        >
                                            Tinjau
                                        </Button>
                                    ) : null}
                                </div>
                            </div>
                        ))}
                        {corrections.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Belum ada permintaan koreksi.
                            </p>
                        ) : null}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">
                            Riwayat kehadiran
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left text-xs text-muted-foreground">
                                    <th className="py-2 pr-3">Tanggal</th>
                                    {canCorrect ? (
                                        <th className="py-2 pr-3">Karyawan</th>
                                    ) : null}
                                    <th className="py-2 pr-3">Masuk</th>
                                    <th className="py-2 pr-3">Pulang</th>
                                    <th className="py-2 pr-3">Kerja</th>
                                    <th className="py-2 pr-3">Telat</th>
                                    <th className="py-2 pr-3">Status</th>
                                    <th className="py-2">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {attendances.data.map((row) => {
                                    const hasPending = row.corrections?.some(
                                        (c) => c.status === 'pending',
                                    );

                                    return (
                                        <tr
                                            className="border-b last:border-0"
                                            key={row.id}
                                        >
                                            <td className="py-2 pr-3">
                                                {row.date}
                                            </td>
                                            {canCorrect ? (
                                                <td className="py-2 pr-3">
                                                    {row.employee?.user.name}
                                                </td>
                                            ) : null}
                                            <td className="py-2 pr-3 tabular-nums">
                                                {row.checked_in_at?.slice(
                                                    11,
                                                    16,
                                                ) ?? '—'}
                                            </td>
                                            <td className="py-2 pr-3 tabular-nums">
                                                {row.checked_out_at?.slice(
                                                    11,
                                                    16,
                                                ) ?? '—'}
                                            </td>
                                            <td className="py-2 pr-3 tabular-nums">
                                                {row.worked_minutes} m
                                            </td>
                                            <td className="py-2 pr-3 tabular-nums">
                                                {row.late_minutes} m
                                            </td>
                                            <td className="py-2 pr-3 capitalize">
                                                {row.status}
                                            </td>
                                            <td className="py-2">
                                                {canCorrect ? (
                                                    <Button
                                                        onClick={() =>
                                                            openCorrect(row)
                                                        }
                                                        size="sm"
                                                        variant="outline"
                                                    >
                                                        <PenLine />
                                                        Koreksi
                                                    </Button>
                                                ) : hasPending ? (
                                                    <Badge variant="secondary">
                                                        Menunggu HR
                                                    </Badge>
                                                ) : (
                                                    <Button
                                                        onClick={() =>
                                                            openRequest(row)
                                                        }
                                                        size="sm"
                                                        variant="outline"
                                                    >
                                                        <MessageSquareWarning />
                                                        Ajukan koreksi
                                                    </Button>
                                                )}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </CardContent>
                </Card>
            </div>

            {/* HR correction: changes the record directly, with a mandatory reason. */}
            <Dialog
                onOpenChange={(open) => !open && setCorrecting(null)}
                open={correcting !== null}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Koreksi kehadiran</DialogTitle>
                        <DialogDescription>
                            Perubahan langsung diterapkan dan dicatat di audit
                            log beserta nilai sebelum dan sesudahnya.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-3">
                        <div className="grid gap-1.5">
                            <Label htmlFor="correct-in">Waktu masuk</Label>
                            <Input
                                id="correct-in"
                                onChange={(e) => setCheckedIn(e.target.value)}
                                type="datetime-local"
                                value={checkedIn}
                            />
                        </div>
                        <div className="grid gap-1.5">
                            <Label htmlFor="correct-out">Waktu pulang</Label>
                            <Input
                                id="correct-out"
                                onChange={(e) => setCheckedOut(e.target.value)}
                                type="datetime-local"
                                value={checkedOut}
                            />
                        </div>
                        <div className="grid gap-1.5">
                            <Label htmlFor="correct-reason">
                                Alasan (minimal 10 karakter)
                            </Label>
                            <Input
                                id="correct-reason"
                                onChange={(e) => setReason(e.target.value)}
                                value={reason}
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button
                            onClick={() => setCorrecting(null)}
                            variant="outline"
                        >
                            Batal
                        </Button>
                        <Button
                            disabled={reason.trim().length < 10}
                            onClick={submitCorrection}
                        >
                            Simpan koreksi
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Employee request: records what was asked for; HR decides. */}
            <Dialog
                onOpenChange={(open) => !open && setRequesting(null)}
                open={requesting !== null}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Ajukan koreksi kehadiran</DialogTitle>
                        <DialogDescription>
                            Catatan kehadiran Anda tidak berubah sampai HR
                            menyetujui permintaan ini.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-3">
                        <div className="grid gap-1.5">
                            <Label htmlFor="request-in">
                                Waktu masuk seharusnya
                            </Label>
                            <Input
                                id="request-in"
                                onChange={(e) => setCheckedIn(e.target.value)}
                                type="datetime-local"
                                value={checkedIn}
                            />
                        </div>
                        <div className="grid gap-1.5">
                            <Label htmlFor="request-out">
                                Waktu pulang seharusnya
                            </Label>
                            <Input
                                id="request-out"
                                onChange={(e) => setCheckedOut(e.target.value)}
                                type="datetime-local"
                                value={checkedOut}
                            />
                        </div>
                        <div className="grid gap-1.5">
                            <Label htmlFor="request-reason">
                                Alasan (minimal 10 karakter)
                            </Label>
                            <Input
                                id="request-reason"
                                onChange={(e) => setReason(e.target.value)}
                                placeholder="Mesin absensi tidak berfungsi pagi itu"
                                value={reason}
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button
                            onClick={() => setRequesting(null)}
                            variant="outline"
                        >
                            Batal
                        </Button>
                        <Button
                            disabled={reason.trim().length < 10}
                            onClick={submitRequest}
                        >
                            Kirim permintaan
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog
                onOpenChange={(open) => !open && setReviewing(null)}
                open={reviewing !== null}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Tinjau permintaan koreksi</DialogTitle>
                        <DialogDescription>
                            {reviewing?.attendance?.employee?.user?.name} ·{' '}
                            {reviewing?.attendance?.date}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-3">
                        <div className="rounded-md border p-3 text-sm">
                            <p className="text-xs text-muted-foreground">
                                Alasan karyawan
                            </p>
                            <p>{reviewing?.reason}</p>
                            {reviewing?.new_values &&
                            Object.keys(reviewing.new_values).length > 0 ? (
                                <p className="mt-2 text-xs text-muted-foreground">
                                    Diminta:{' '}
                                    {Object.entries(reviewing.new_values)
                                        .map(
                                            ([key, value]) =>
                                                `${key} → ${value}`,
                                        )
                                        .join(', ')}
                                </p>
                            ) : null}
                        </div>
                        <div className="grid gap-1.5">
                            <Label htmlFor="review-notes">
                                Catatan (opsional)
                            </Label>
                            <Input
                                id="review-notes"
                                onChange={(e) => setReviewNotes(e.target.value)}
                                value={reviewNotes}
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button
                            onClick={() => review('reject')}
                            variant="outline"
                        >
                            <X />
                            Tolak
                        </Button>
                        <Button onClick={() => review('approve')}>
                            <Check />
                            Setujui
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
