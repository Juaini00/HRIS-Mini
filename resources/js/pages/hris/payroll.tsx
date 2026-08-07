import { Form, Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
export default function Payroll({
  periods,
  canManage,
}: {
  periods: { data: Array<any> };
  canManage: boolean;
}) {
  return (
    <>
      <Head title="Payroll" />
      <div className="flex flex-col gap-6 p-4 md:p-6">
        <div>
          <h1 className="text-2xl font-semibold">Payroll</h1>
          <p className="text-muted-foreground">
            Generate, tinjau, dan publikasikan penggajian.
          </p>
        </div>
        {canManage && (
          <Card>
            <CardHeader>
              <CardTitle>Buat periode</CardTitle>
            </CardHeader>
            <CardContent>
              <Form
                action="/payroll"
                method="post"
                className="grid gap-3 md:grid-cols-4"
              >
                <Input name="name" placeholder="Nama periode" required />
                <Input name="starts_on" type="date" required />
                <Input name="ends_on" type="date" required />
                <Button>Generate payroll</Button>
              </Form>
            </CardContent>
          </Card>
        )}
        <div className="grid gap-4 lg:grid-cols-2">
          {periods.data.map((p) => (
            <Card key={p.id}>
              <CardHeader>
                <CardTitle className="flex justify-between">
                  {p.name}
                  <span className="text-sm font-normal capitalize">
                    {p.status}
                  </span>
                </CardTitle>
              </CardHeader>
              <CardContent className="flex flex-col gap-3">
                <p className="text-sm text-muted-foreground">
                  {p.starts_on} – {p.ends_on}
                </p>
                {p.records.map((r: any) => (
                  <div
                    key={r.id}
                    className="flex justify-between border-t pt-2"
                  >
                    <span>{r.employee?.user?.name ?? 'Payslip saya'}</span>
                    <strong>
                      Rp {Number(r.net_salary).toLocaleString('id-ID')}
                    </strong>
                  </div>
                ))}
                {canManage && p.status === 'draft' && (
                  <Button
                    onClick={() => router.post(`/payroll/${p.id}/publish`)}
                  >
                    Publikasikan
                  </Button>
                )}
              </CardContent>
            </Card>
          ))}
        </div>
      </div>
    </>
  );
}
