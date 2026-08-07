import { Form, Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
export default function Announcements({
  announcements,
  canManage,
}: {
  announcements: { data: Array<any> };
  canManage: boolean;
}) {
  return (
    <>
      <Head title="Pengumuman" />
      <div className="flex flex-col gap-6 p-4 md:p-6">
        <div>
          <h1 className="text-2xl font-semibold">Pengumuman</h1>
          <p className="text-muted-foreground">
            Informasi internal untuk seluruh tim.
          </p>
        </div>
        {canManage && (
          <Card>
            <CardHeader>
              <CardTitle>Buat pengumuman</CardTitle>
            </CardHeader>
            <CardContent>
              <Form
                action="/announcements"
                method="post"
                className="grid gap-3"
              >
                <Input name="title" placeholder="Judul" required />
                <textarea
                  name="body"
                  className="min-h-24 rounded-md border bg-background p-3"
                  required
                />
                <div className="flex gap-3">
                  <select
                    name="audience"
                    className="h-9 rounded-md border bg-background px-3"
                  >
                    <option value="all">Semua</option>
                    <option value="manager">Manager</option>
                    <option value="employee">Employee</option>
                  </select>
                  <Input name="published_at" type="datetime-local" />
                  <Button>Simpan</Button>
                </div>
              </Form>
            </CardContent>
          </Card>
        )}
        <div className="grid gap-4 md:grid-cols-2">
          {announcements.data.map((a) => (
            <Card key={a.id}>
              <CardHeader>
                <CardTitle>{a.title}</CardTitle>
              </CardHeader>
              <CardContent>
                <p className="whitespace-pre-line text-sm">{a.body}</p>
                <p className="mt-4 text-xs text-muted-foreground">
                  Audiens: {a.audience}
                </p>
              </CardContent>
            </Card>
          ))}
        </div>
      </div>
    </>
  );
}
