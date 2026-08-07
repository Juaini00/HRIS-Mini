import { Head } from '@inertiajs/react';
import { Card, CardContent } from '@/components/ui/card';

type AuditLog = {
    id: number;
    event: string;
    auditable_type?: string;
    auditable_id?: number;
    ip_address?: string;
    created_at: string;
    user?: { name: string; email: string };
    metadata?: Record<string, unknown>;
};

export default function AuditLogs({ logs }: { logs: { data: AuditLog[] } }) {
    return (
        <>
            <Head title="Audit log" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Audit log</h1>
                    <p className="text-muted-foreground">
                        Jejak aktivitas sensitif dan perubahan data.
                    </p>
                </div>
                <Card>
                    <CardContent className="overflow-x-auto p-0">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left">
                                    <th className="p-4">Waktu</th>
                                    <th>Pelaku</th>
                                    <th>Aktivitas</th>
                                    <th>Subjek</th>
                                    <th>IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                {logs.data.map((log) => (
                                    <tr key={log.id} className="border-b">
                                        <td className="p-4">
                                            {log.created_at}
                                        </td>
                                        <td>{log.user?.name ?? 'System'}</td>
                                        <td>{log.event}</td>
                                        <td>
                                            {log.auditable_type
                                                ? `${log.auditable_type} #${log.auditable_id}`
                                                : '-'}
                                        </td>
                                        <td>{log.ip_address ?? '-'}</td>
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
