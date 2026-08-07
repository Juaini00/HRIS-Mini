import { Head, Link, router } from '@inertiajs/react';
import { Building2, CalendarDays, Clock3, Users } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';

type Props = {
  stats: {
    employees: number;
    departments: number;
    pendingLeave: number;
    presentToday: number;
  };
  myAttendance?: { checked_in_at?: string; checked_out_at?: string };
  announcements: Array<{ id: number; title: string; body: string }>;
};
export default function Dashboard({
  stats,
  myAttendance,
  announcements,
}: Props) {
  const cards = [
    ['Karyawan aktif', stats.employees, Users],
    ['Departemen', stats.departments, Building2],
    ['Cuti menunggu', stats.pendingLeave, CalendarDays],
    ['Hadir hari ini', stats.presentToday, Clock3],
  ] as const;
  return (
    <>
      <Head title="Dashboard" />
      <div className="flex flex-col gap-6 p-4 md:p-6">
        <div>
          <h1 className="text-2xl font-semibold">Selamat datang di NusaHR</h1>
          <p className="text-muted-foreground">
            Ringkasan operasional sumber daya manusia hari ini.
          </p>
        </div>
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          {cards.map(([label, value, Icon]) => (
            <Card key={label}>
              <CardContent className="flex items-center justify-between p-5">
                <div>
                  <p className="text-sm text-muted-foreground">{label}</p>
                  <p className="text-3xl font-semibold">{value}</p>
                </div>
                <Icon className="size-9 text-primary" />
              </CardContent>
            </Card>
          ))}
        </div>
        <div className="grid gap-6 lg:grid-cols-3">
          <Card>
            <CardHeader>
              <CardTitle>Presensi saya</CardTitle>
            </CardHeader>
            <CardContent className="flex flex-col gap-3">
              <p className="text-sm text-muted-foreground">
                {myAttendance?.checked_in_at
                  ? 'Sudah check-in'
                  : 'Belum check-in hari ini'}
              </p>
              <div className="flex gap-2">
                <Button
                  onClick={() => router.post('/attendance/check-in')}
                  disabled={!!myAttendance?.checked_in_at}
                >
                  Check-in
                </Button>
                <Button
                  variant="outline"
                  onClick={() => router.post('/attendance/check-out')}
                  disabled={
                    !myAttendance?.checked_in_at ||
                    !!myAttendance?.checked_out_at
                  }
                >
                  Check-out
                </Button>
              </div>
            </CardContent>
          </Card>
          <Card className="lg:col-span-2">
            <CardHeader>
              <CardTitle>Pengumuman terbaru</CardTitle>
            </CardHeader>
            <CardContent className="flex flex-col gap-4">
              {announcements.length ? (
                announcements.map((item) => (
                  <div key={item.id} className="border-b pb-3 last:border-0">
                    <p className="font-medium">{item.title}</p>
                    <p className="line-clamp-2 text-sm text-muted-foreground">
                      {item.body}
                    </p>
                  </div>
                ))
              ) : (
                <p className="text-sm text-muted-foreground">
                  Belum ada pengumuman.
                </p>
              )}
              <Link
                className="text-sm font-medium text-primary"
                href="/announcements"
              >
                Lihat semua
              </Link>
            </CardContent>
          </Card>
        </div>
      </div>
    </>
  );
}
Dashboard.layout = { breadcrumbs: [{ title: 'Dashboard', href: dashboard() }] };
