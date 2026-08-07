import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
export default function Attendance({
  attendances,
  today,
}: {
  attendances: { data: Array<any> };
  today?: any;
}) {
  return (
    <>
      <Head title="Kehadiran" />
      <div className="flex flex-col gap-6 p-4 md:p-6">
        <div>
          <h1 className="text-2xl font-semibold">Kehadiran</h1>
          <p className="text-muted-foreground">
            Catatan check-in dan check-out harian.
          </p>
        </div>
        <Card>
          <CardHeader>
            <CardTitle>Presensi hari ini</CardTitle>
          </CardHeader>
          <CardContent className="flex items-center gap-3">
            <Button
              disabled={!!today?.checked_in_at}
              onClick={() => router.post('/attendance/check-in')}
            >
              Check-in
            </Button>
            <Button
              variant="outline"
              disabled={!today?.checked_in_at || !!today?.checked_out_at}
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
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                {attendances.data.map((a) => (
                  <tr key={a.id} className="border-b">
                    <td className="p-4">{a.employee?.user?.name}</td>
                    <td>{a.date}</td>
                    <td>{a.checked_in_at ?? '-'}</td>
                    <td>{a.checked_out_at ?? '-'}</td>
                    <td>{a.worked_minutes} menit</td>
                    <td>{a.status}</td>
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
