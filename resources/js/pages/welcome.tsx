import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, CalendarCheck, ShieldCheck, Users } from 'lucide-react';
import { dashboard, login } from '@/routes';

export default function Welcome() {
    const { auth } = usePage().props;
    return (
        <>
            <Head title="Human Resource Information System" />
            <main className="min-h-screen bg-background text-foreground">
                <nav className="mx-auto flex max-w-6xl items-center justify-between px-6 py-6">
                    <div className="flex items-center gap-3">
                        <div className="grid size-10 place-items-center rounded-xl bg-primary font-bold text-primary-foreground">
                            N
                        </div>
                        <div>
                            <p className="font-semibold">NusaHR</p>
                            <p className="text-xs text-muted-foreground">
                                People operations, simplified
                            </p>
                        </div>
                    </div>
                    <Link
                        href={auth.user ? dashboard() : login()}
                        className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground"
                    >
                        {auth.user ? 'Buka dashboard' : 'Masuk'}
                        <ArrowRight className="size-4" />
                    </Link>
                </nav>
                <section className="mx-auto grid max-w-6xl items-center gap-12 px-6 py-20 lg:grid-cols-2 lg:py-28">
                    <div className="grid gap-6">
                        <span className="w-fit rounded-full bg-primary/10 px-3 py-1 text-sm font-medium text-primary">
                            HRIS untuk tim Indonesia
                        </span>
                        <h1 className="text-4xl font-bold tracking-tight sm:text-6xl">
                            Kelola manusia, waktu, dan payroll dalam satu
                            tempat.
                        </h1>
                        <p className="max-w-xl text-lg text-muted-foreground">
                            NusaHR membantu perusahaan mengelola data karyawan,
                            cuti, kehadiran, payroll, pengumuman, dokumen, dan
                            laporan dengan kontrol akses yang aman.
                        </p>
                        <Link
                            href={auth.user ? dashboard() : login()}
                            className="inline-flex w-fit items-center gap-2 rounded-lg bg-primary px-5 py-3 font-medium text-primary-foreground"
                        >
                            {auth.user
                                ? 'Ke dashboard'
                                : 'Masuk sebagai karyawan'}
                            <ArrowRight className="size-4" />
                        </Link>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="rounded-2xl border bg-card p-6 shadow-sm sm:col-span-2">
                            <Users className="mb-8 size-10 text-primary" />
                            <p className="text-3xl font-semibold">
                                Satu sumber data
                            </p>
                            <p className="mt-2 text-muted-foreground">
                                Profil, organisasi, dokumen, dan riwayat
                                pekerjaan tetap konsisten.
                            </p>
                        </div>
                        <div className="rounded-2xl border bg-card p-6 shadow-sm">
                            <CalendarCheck className="mb-8 size-9 text-emerald-600" />
                            <p className="font-semibold">Workflow terukur</p>
                            <p className="mt-2 text-sm text-muted-foreground">
                                Cuti dan presensi dengan saldo serta approval
                                yang konsisten.
                            </p>
                        </div>
                        <div className="rounded-2xl border bg-card p-6 shadow-sm">
                            <ShieldCheck className="mb-8 size-9 text-blue-600" />
                            <p className="font-semibold">Akses terlindungi</p>
                            <p className="mt-2 text-sm text-muted-foreground">
                                Data sensitif dibatasi dan aktivitas penting
                                diaudit.
                            </p>
                        </div>
                    </div>
                </section>
            </main>
        </>
    );
}
