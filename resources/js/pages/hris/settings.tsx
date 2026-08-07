import { Form, Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';

type Props = { settings: Record<string, string> };

export default function CompanySettings({ settings }: Props) {
    return (
        <>
            <Head title="Pengaturan perusahaan" />
            <div className="flex max-w-3xl flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold">
                        Pengaturan perusahaan
                    </h1>
                    <p className="text-muted-foreground">
                        Atur identitas dan kebijakan waktu kerja NusaHR.
                    </p>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Konfigurasi umum</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Form
                            action="/company-settings"
                            method="put"
                            className="grid gap-4 md:grid-cols-2"
                        >
                            <label className="grid gap-2 text-sm">
                                Nama perusahaan
                                <Input
                                    name="company_name"
                                    defaultValue={
                                        settings.company_name ?? 'NusaHR'
                                    }
                                    required
                                />
                            </label>
                            <label className="grid gap-2 text-sm">
                                Mata uang
                                <select
                                    name="currency"
                                    defaultValue={settings.currency ?? 'IDR'}
                                    className="h-9 rounded-md border bg-background px-3"
                                >
                                    <option value="IDR">IDR</option>
                                </select>
                            </label>
                            <label className="grid gap-2 text-sm">
                                Jam mulai
                                <Input
                                    name="work_starts_at"
                                    type="time"
                                    defaultValue={
                                        settings.work_starts_at ?? '08:00'
                                    }
                                    required
                                />
                            </label>
                            <label className="grid gap-2 text-sm">
                                Jam selesai
                                <Input
                                    name="work_ends_at"
                                    type="time"
                                    defaultValue={
                                        settings.work_ends_at ?? '17:00'
                                    }
                                    required
                                />
                            </label>
                            <label className="grid gap-2 text-sm">
                                Toleransi terlambat
                                <Input
                                    name="late_tolerance_minutes"
                                    type="number"
                                    min="0"
                                    max="180"
                                    defaultValue={
                                        settings.late_tolerance_minutes ?? '15'
                                    }
                                    required
                                />
                            </label>
                            <div className="flex items-end">
                                <Button>Simpan pengaturan</Button>
                            </div>
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
