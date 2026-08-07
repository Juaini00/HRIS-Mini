import { Head, Link } from '@inertiajs/react';
import { LockKeyhole, ServerCrash, SearchX, TriangleAlert } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';

type Props = {
    status: number;
};

/** Copy for the statuses we render deliberately; anything else falls back to a generic message. */
const KNOWN: Record<
    number,
    { title: string; description: string; icon: typeof LockKeyhole }
> = {
    403: {
        title: 'Akses ditolak',
        description:
            'Akun Anda tidak memiliki izin untuk membuka halaman ini. Hubungi administrator HR jika Anda merasa seharusnya punya akses.',
        icon: LockKeyhole,
    },
    404: {
        title: 'Halaman tidak ditemukan',
        description:
            'Halaman yang Anda cari sudah dipindahkan atau tidak pernah ada. Periksa kembali tautannya.',
        icon: SearchX,
    },
    419: {
        title: 'Sesi kedaluwarsa',
        description:
            'Sesi Anda berakhir karena tidak ada aktivitas. Muat ulang halaman lalu coba lagi.',
        icon: TriangleAlert,
    },
    500: {
        title: 'Terjadi kesalahan',
        description:
            'Sistem mengalami gangguan saat memproses permintaan Anda. Tim teknis sudah dicatat kejadiannya.',
        icon: ServerCrash,
    },
    503: {
        title: 'Sedang dalam pemeliharaan',
        description:
            'Aplikasi sedang diperbarui. Silakan coba beberapa saat lagi.',
        icon: ServerCrash,
    },
};

export default function ErrorPage({ status }: Props) {
    const detail = KNOWN[status] ?? {
        title: 'Terjadi kesalahan',
        description: 'Permintaan Anda tidak dapat diselesaikan.',
        icon: TriangleAlert,
    };
    const Icon = detail.icon;

    return (
        <>
            <Head title={`${status} — ${detail.title}`} />
            <main className="flex min-h-screen items-center justify-center p-6">
                <div className="w-full max-w-md text-center">
                    <div className="mx-auto flex size-14 items-center justify-center rounded-full bg-muted">
                        <Icon
                            aria-hidden
                            className="size-7 text-muted-foreground"
                        />
                    </div>
                    <p className="mt-6 text-sm font-medium text-muted-foreground tabular-nums">
                        Error {status}
                    </p>
                    <h1 className="mt-1 text-2xl font-semibold">
                        {detail.title}
                    </h1>
                    <p className="mt-3 text-sm text-muted-foreground">
                        {detail.description}
                    </p>
                    <div className="mt-8 flex flex-wrap items-center justify-center gap-3">
                        <Button asChild>
                            <Link href={dashboard()}>Kembali ke dashboard</Link>
                        </Button>
                        <Button asChild variant="outline">
                            <a href="/">Halaman utama</a>
                        </Button>
                    </div>
                </div>
            </main>
        </>
    );
}
