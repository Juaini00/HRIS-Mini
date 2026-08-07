import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
type AttendanceRow = {
    id: number;
    date: string;
    checked_in_at?: string;
    checked_out_at?: string;
    worked_minutes: number;
    late_minutes: number;
    status: string;
    employee?: { user: { name: string } };
};
export default function Attendance({
    attendances,
    today,
    canCorrect,
}: {
    attendances: { data: AttendanceRow[] };
    today?: AttendanceRow;
    canCorrect: boolean;
}) {
    const correct = (row: AttendanceRow) => {
        const reason = window.prompt('Alasan koreksi (minimal 10 karakter)');
        if (!reason) return;
        router.patch(`/attendance/${row.id}/correct`, {
            checked_in_at: row.checked_in_at,
            checked_out_at: row.checked_out_at,
            status: row.status,
            correction_reason: reason,
        });
    };
    return (
        <>
            <Head title="Kehadiran" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Kehadiran</h1>
                    <p className="text-muted-foreground">
                        Catatan check-in, check-out, keterlambatan, dan koreksi
                        terotorisasi.
                    </p>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Presensi hari ini</CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-wrap items-center gap-3">
                        <Button
                            disabled={!!today?.checked_in_at}
                            onClick={() => router.post('/attendance/check-in')}
                        >
                            Check-in
                        </Button>
                        <Button
                            variant="outline"
                            disabled={
                                !today?.checked_in_at || !!today?.checked_out_at
                            }
                            onClick={() => router.post('/attendance/check-out')}
                        >
                            Check-out
                        </Button>
                        <span className="text-sm text-muted-foreground">
                            {today?.worked_minutes ?? 0} menit bekerja
                        </span>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="overflow-x-auto p-0">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left">
                                    <th className="p-4">Karyawan</th>
                                    <th>Tanggal</th>
                                    <th>Masuk</th>
                                    <th>Keluar</th>
                                    <th>Durasi</th>
                                    <th>Terlambat</th>
                                    <th>Status</th>
                                    {canCorrect && <th>Aksi</th>}
                                </tr>
                            </thead>
                            <tbody>
                                {attendances.data.map((row) => (
                                    <tr key={row.id} className="border-b">
                                        <td className="p-4">
                                            {row.employee?.user?.name ?? 'Saya'}
                                        </td>
                                        <td>{row.date}</td>
                                        <td>{row.checked_in_at ?? '-'}</td>
                                        <td>{row.checked_out_at ?? '-'}</td>
                                        <td>{row.worked_minutes} menit</td>
                                        <td>{row.late_minutes} menit</td>
                                        <td>{row.status}</td>
                                        {canCorrect && (
                                            <td>
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => correct(row)}
                                                >
                                                    Koreksi
                                                </Button>
                                            </td>
                                        )}
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
