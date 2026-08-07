import { Form, Head, Link, router } from '@inertiajs/react';
import { Download, Trash2, Upload } from 'lucide-react';
import { useRef, useState } from 'react';
import { CurrencyInput } from '@/components/currency-input';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

type Option = { id: number; name: string };

type Document = {
    id: number;
    name: string;
    title?: string;
    category?: string;
    size: number;
    visibility?: string;
    expires_at?: string;
    uploader?: { name: string };
};

type Employee = {
    id: number;
    employee_number: string;
    photo_path?: string;
    first_name?: string;
    last_name?: string;
    preferred_name?: string;
    work_email?: string;
    personal_email?: string;
    gender?: string;
    date_of_birth?: string;
    place_of_birth?: string;
    nationality?: string;
    marital_status?: string;
    employment_status?: string;
    work_schedule_type?: string;
    joined_at: string;
    ended_at?: string;
    probation_ends_on?: string;
    contract_starts_on?: string;
    contract_ends_on?: string;
    phone?: string;
    address?: string;
    city?: string;
    province?: string;
    postal_code?: string;
    country?: string;
    emergency_contact_name?: string;
    emergency_contact_relationship?: string;
    emergency_contact_phone?: string;
    basic_salary?: string;
    bank_name?: string;
    bank_account?: string;
    bank_account_holder?: string;
    tax_number?: string;
    notes?: string;
    user: { name: string; email: string; role: string };
    department?: { name: string };
    position?: { name: string };
    location?: { name: string };
    employment_type?: { name: string };
    manager?: { user: { name: string } };
    department_id: number;
    position_id: number;
    location_id?: number;
    employment_type_id?: number;
    manager_id?: number;
    documents: Document[];
    reports?: Array<{
        id: number;
        employee_number: string;
        user: { name: string };
    }>;
    salary_histories?: Array<{
        id: number;
        amount: string;
        effective_from: string;
        effective_to?: string;
        notes?: string;
    }>;
};

type Summaries = {
    attendance: {
        thisMonth: Record<string, number>;
        recent: Array<{
            id: number;
            date: string;
            status: string;
            checked_in_at?: string;
            checked_out_at?: string;
            worked_minutes: number;
            late_minutes: number;
        }>;
    };
    leave: {
        balances: Array<{
            id: number;
            remaining: number;
            entitled: string;
            used: string;
            pending: string;
            leave_type?: { name: string; color?: string };
        }>;
        recent: Array<{
            id: number;
            request_number?: string;
            start_date: string;
            end_date: string;
            days: string;
            status: string;
            leave_type?: { name: string };
        }>;
    };
    payroll: Array<{
        id: number;
        basic_salary: string;
        earnings: string;
        deductions: string;
        net_salary: string;
        period?: { name: string; payment_date?: string; status: string };
    }>;
};

type Props = {
    employee: Employee;
    canUpdate: boolean;
    canSeeSensitive: boolean;
    summaries: Summaries;
    timeline: Array<{
        id: number;
        event: string;
        description?: string;
        created_at: string;
        user?: { name: string };
    }>;
    departments: Option[];
    positions: Option[];
    locations: Option[];
    employmentTypes: Option[];
    managers: Array<{ id: number; user: { name: string } }>;
};

const currency = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

function Field({ label, value }: { label: string; value?: string | null }) {
    return (
        <div className="grid gap-0.5">
            <dt className="text-xs text-muted-foreground">{label}</dt>
            <dd className="text-sm">{value || '—'}</dd>
        </div>
    );
}

function FieldGrid({ children }: { children: React.ReactNode }) {
    return (
        <dl className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">{children}</dl>
    );
}

function initials(name: string) {
    return name
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

export default function EmployeeDetail({
    employee,
    canUpdate,
    canSeeSensitive,
    summaries,
    timeline,
    departments,
    positions,
    locations,
    employmentTypes,
    managers,
}: Props) {
    const photoInput = useRef<HTMLInputElement>(null);
    const [confirmingDeactivate, setConfirmingDeactivate] = useState(false);
    const [deactivateReason, setDeactivateReason] = useState('');
    const [deactivateStatus, setDeactivateStatus] = useState('resigned');

    const uploadPhoto = (file: File) => {
        router.post(
            `/employees/${employee.id}/photo`,
            { photo: file },
            { forceFormData: true, preserveScroll: true },
        );
    };

    const deactivate = () => {
        router.patch(
            `/employees/${employee.id}/deactivate`,
            {
                ended_at: new Date().toISOString().slice(0, 10),
                reason: deactivateReason,
                employment_status: deactivateStatus,
            },
            { onFinish: () => setConfirmingDeactivate(false) },
        );
    };

    return (
        <>
            <Head title={employee.user.name} />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="flex items-center gap-4">
                        <div className="relative">
                            <Avatar className="size-16">
                                {employee.photo_path ? (
                                    <AvatarImage
                                        alt={employee.user.name}
                                        src={`/storage/${employee.photo_path}`}
                                    />
                                ) : null}
                                <AvatarFallback>
                                    {initials(employee.user.name)}
                                </AvatarFallback>
                            </Avatar>
                            {canUpdate ? (
                                <>
                                    <input
                                        accept="image/jpeg,image/png,image/webp"
                                        className="sr-only"
                                        onChange={(event) => {
                                            const file =
                                                event.target.files?.[0];

                                            if (file) {
                                                uploadPhoto(file);
                                            }
                                        }}
                                        ref={photoInput}
                                        type="file"
                                    />
                                    <Button
                                        aria-label="Ganti foto profil"
                                        className="absolute -right-1 -bottom-1 size-7 rounded-full"
                                        onClick={() =>
                                            photoInput.current?.click()
                                        }
                                        size="icon"
                                        variant="secondary"
                                    >
                                        <Upload className="size-3.5" />
                                    </Button>
                                </>
                            ) : null}
                        </div>
                        <div>
                            <p className="text-sm text-muted-foreground">
                                {employee.employee_number}
                            </p>
                            <h1 className="text-2xl font-semibold">
                                {employee.user.name}
                            </h1>
                            <p className="text-muted-foreground">
                                {employee.position?.name} ·{' '}
                                {employee.department?.name}
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        {employee.employment_status ? (
                            <Badge variant="secondary">
                                {employee.employment_status}
                            </Badge>
                        ) : null}
                        {canUpdate && !employee.ended_at ? (
                            <Button
                                onClick={() => setConfirmingDeactivate(true)}
                                variant="outline"
                            >
                                Nonaktifkan
                            </Button>
                        ) : null}
                        <Button asChild variant="outline">
                            <Link href="/employees">Kembali</Link>
                        </Button>
                    </div>
                </div>

                <Tabs defaultValue="profile">
                    <TabsList className="flex-wrap">
                        <TabsTrigger value="profile">Profil</TabsTrigger>
                        <TabsTrigger value="employment">
                            Kepegawaian
                        </TabsTrigger>
                        <TabsTrigger value="attendance">Kehadiran</TabsTrigger>
                        <TabsTrigger value="leave">Cuti</TabsTrigger>
                        {canSeeSensitive ? (
                            <TabsTrigger value="compensation">
                                Kompensasi
                            </TabsTrigger>
                        ) : null}
                        <TabsTrigger value="documents">Dokumen</TabsTrigger>
                        <TabsTrigger value="activity">Aktivitas</TabsTrigger>
                    </TabsList>

                    <TabsContent
                        className="mt-4 flex flex-col gap-4"
                        value="profile"
                    >
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Data pribadi
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <FieldGrid>
                                    <Field
                                        label="Nama depan"
                                        value={employee.first_name}
                                    />
                                    <Field
                                        label="Nama belakang"
                                        value={employee.last_name}
                                    />
                                    <Field
                                        label="Nama panggilan"
                                        value={employee.preferred_name}
                                    />
                                    <Field
                                        label="Jenis kelamin"
                                        value={employee.gender}
                                    />
                                    <Field
                                        label="Tanggal lahir"
                                        value={employee.date_of_birth}
                                    />
                                    <Field
                                        label="Tempat lahir"
                                        value={employee.place_of_birth}
                                    />
                                    <Field
                                        label="Kewarganegaraan"
                                        value={employee.nationality}
                                    />
                                    <Field
                                        label="Status pernikahan"
                                        value={employee.marital_status}
                                    />
                                </FieldGrid>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Kontak
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <FieldGrid>
                                    <Field
                                        label="Email kantor"
                                        value={
                                            employee.work_email ??
                                            employee.user.email
                                        }
                                    />
                                    <Field
                                        label="Telepon"
                                        value={employee.phone}
                                    />
                                    {canSeeSensitive ? (
                                        <>
                                            <Field
                                                label="Email pribadi"
                                                value={employee.personal_email}
                                            />
                                            <Field
                                                label="Alamat"
                                                value={employee.address}
                                            />
                                            <Field
                                                label="Kota"
                                                value={employee.city}
                                            />
                                            <Field
                                                label="Provinsi"
                                                value={employee.province}
                                            />
                                            <Field
                                                label="Kode pos"
                                                value={employee.postal_code}
                                            />
                                        </>
                                    ) : null}
                                    <Field
                                        label="Negara"
                                        value={employee.country}
                                    />
                                </FieldGrid>
                            </CardContent>
                        </Card>

                        {canSeeSensitive ? (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        Kontak darurat
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <FieldGrid>
                                        <Field
                                            label="Nama"
                                            value={
                                                employee.emergency_contact_name
                                            }
                                        />
                                        <Field
                                            label="Hubungan"
                                            value={
                                                employee.emergency_contact_relationship
                                            }
                                        />
                                        <Field
                                            label="Telepon"
                                            value={
                                                employee.emergency_contact_phone
                                            }
                                        />
                                    </FieldGrid>
                                </CardContent>
                            </Card>
                        ) : null}

                        {canUpdate ? (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        Ubah data pribadi
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <Form
                                        action={`/employees/${employee.id}/profile`}
                                        className="grid gap-3 md:grid-cols-3"
                                        method="put"
                                    >
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="p-first-name">
                                                Nama depan
                                            </Label>
                                            <Input
                                                id="p-first-name"
                                                name="first_name"
                                                defaultValue={
                                                    employee.first_name ?? ''
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="p-last-name">
                                                Nama belakang
                                            </Label>
                                            <Input
                                                id="p-last-name"
                                                name="last_name"
                                                defaultValue={
                                                    employee.last_name ?? ''
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="p-preferred-name">
                                                Nama panggilan
                                            </Label>
                                            <Input
                                                id="p-preferred-name"
                                                name="preferred_name"
                                                defaultValue={
                                                    employee.preferred_name ??
                                                    ''
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="p-gender">
                                                Jenis kelamin
                                            </Label>
                                            <select
                                                id="p-gender"
                                                name="gender"
                                                className="h-9 rounded-md border bg-background px-3 text-sm"
                                                defaultValue={
                                                    employee.gender ?? ''
                                                }
                                            >
                                                <option value="">—</option>
                                                <option value="male">
                                                    Laki-laki
                                                </option>
                                                <option value="female">
                                                    Perempuan
                                                </option>
                                            </select>
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="p-dob">
                                                Tanggal lahir
                                            </Label>
                                            <Input
                                                id="p-dob"
                                                name="date_of_birth"
                                                type="date"
                                                defaultValue={
                                                    employee.date_of_birth ?? ''
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="p-pob">
                                                Tempat lahir
                                            </Label>
                                            <Input
                                                id="p-pob"
                                                name="place_of_birth"
                                                defaultValue={
                                                    employee.place_of_birth ??
                                                    ''
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="p-nationality">
                                                Kewarganegaraan
                                            </Label>
                                            <Input
                                                id="p-nationality"
                                                name="nationality"
                                                defaultValue={
                                                    employee.nationality ?? ''
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="p-marital">
                                                Status pernikahan
                                            </Label>
                                            <select
                                                id="p-marital"
                                                name="marital_status"
                                                className="h-9 rounded-md border bg-background px-3 text-sm"
                                                defaultValue={
                                                    employee.marital_status ??
                                                    ''
                                                }
                                            >
                                                <option value="">—</option>
                                                <option value="single">
                                                    Lajang
                                                </option>
                                                <option value="married">
                                                    Menikah
                                                </option>
                                                <option value="divorced">
                                                    Cerai
                                                </option>
                                                <option value="widowed">
                                                    Janda/Duda
                                                </option>
                                            </select>
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="p-phone">
                                                Telepon
                                            </Label>
                                            <Input
                                                id="p-phone"
                                                name="phone"
                                                defaultValue={
                                                    employee.phone ?? ''
                                                }
                                            />
                                        </div>
                                        {canSeeSensitive ? (
                                            <>
                                                <div className="grid gap-1.5">
                                                    <Label htmlFor="p-personal-email">
                                                        Email pribadi
                                                    </Label>
                                                    <Input
                                                        id="p-personal-email"
                                                        name="personal_email"
                                                        type="email"
                                                        defaultValue={
                                                            employee.personal_email ??
                                                            ''
                                                        }
                                                    />
                                                </div>
                                                <div className="grid gap-1.5 md:col-span-2">
                                                    <Label htmlFor="p-address">
                                                        Alamat
                                                    </Label>
                                                    <Input
                                                        id="p-address"
                                                        name="address"
                                                        defaultValue={
                                                            employee.address ??
                                                            ''
                                                        }
                                                    />
                                                </div>
                                                <div className="grid gap-1.5">
                                                    <Label htmlFor="p-city">
                                                        Kota
                                                    </Label>
                                                    <Input
                                                        id="p-city"
                                                        name="city"
                                                        defaultValue={
                                                            employee.city ?? ''
                                                        }
                                                    />
                                                </div>
                                                <div className="grid gap-1.5">
                                                    <Label htmlFor="p-province">
                                                        Provinsi
                                                    </Label>
                                                    <Input
                                                        id="p-province"
                                                        name="province"
                                                        defaultValue={
                                                            employee.province ??
                                                            ''
                                                        }
                                                    />
                                                </div>
                                                <div className="grid gap-1.5">
                                                    <Label htmlFor="p-postal">
                                                        Kode pos
                                                    </Label>
                                                    <Input
                                                        id="p-postal"
                                                        name="postal_code"
                                                        defaultValue={
                                                            employee.postal_code ??
                                                            ''
                                                        }
                                                    />
                                                </div>
                                            </>
                                        ) : null}
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="p-country">
                                                Negara
                                            </Label>
                                            <Input
                                                id="p-country"
                                                name="country"
                                                defaultValue={
                                                    employee.country ?? ''
                                                }
                                            />
                                        </div>
                                        {canSeeSensitive ? (
                                            <>
                                                <div className="grid gap-1.5">
                                                    <Label htmlFor="p-ec-name">
                                                        Kontak darurat — nama
                                                    </Label>
                                                    <Input
                                                        id="p-ec-name"
                                                        name="emergency_contact_name"
                                                        defaultValue={
                                                            employee.emergency_contact_name ??
                                                            ''
                                                        }
                                                    />
                                                </div>
                                                <div className="grid gap-1.5">
                                                    <Label htmlFor="p-ec-rel">
                                                        Kontak darurat —
                                                        hubungan
                                                    </Label>
                                                    <Input
                                                        id="p-ec-rel"
                                                        name="emergency_contact_relationship"
                                                        defaultValue={
                                                            employee.emergency_contact_relationship ??
                                                            ''
                                                        }
                                                    />
                                                </div>
                                                <div className="grid gap-1.5">
                                                    <Label htmlFor="p-ec-phone">
                                                        Kontak darurat — telepon
                                                    </Label>
                                                    <Input
                                                        id="p-ec-phone"
                                                        name="emergency_contact_phone"
                                                        defaultValue={
                                                            employee.emergency_contact_phone ??
                                                            ''
                                                        }
                                                    />
                                                </div>
                                            </>
                                        ) : null}
                                        <Button className="md:col-span-3 md:justify-self-start">
                                            Simpan data pribadi
                                        </Button>
                                    </Form>
                                </CardContent>
                            </Card>
                        ) : null}
                    </TabsContent>

                    <TabsContent
                        className="mt-4 flex flex-col gap-4"
                        value="employment"
                    >
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Informasi kepegawaian
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <FieldGrid>
                                    <Field
                                        label="Departemen"
                                        value={employee.department?.name}
                                    />
                                    <Field
                                        label="Posisi"
                                        value={employee.position?.name}
                                    />
                                    <Field
                                        label="Lokasi"
                                        value={employee.location?.name}
                                    />
                                    <Field
                                        label="Tipe kepegawaian"
                                        value={employee.employment_type?.name}
                                    />
                                    <Field
                                        label="Manager"
                                        value={employee.manager?.user.name}
                                    />
                                    <Field
                                        label="Status"
                                        value={employee.employment_status}
                                    />
                                    <Field
                                        label="Pola kerja"
                                        value={employee.work_schedule_type}
                                    />
                                    <Field
                                        label="Tanggal bergabung"
                                        value={employee.joined_at}
                                    />
                                    <Field
                                        label="Akhir probation"
                                        value={employee.probation_ends_on}
                                    />
                                    <Field
                                        label="Mulai kontrak"
                                        value={employee.contract_starts_on}
                                    />
                                    <Field
                                        label="Akhir kontrak"
                                        value={employee.contract_ends_on}
                                    />
                                    <Field
                                        label="Tanggal keluar"
                                        value={employee.ended_at}
                                    />
                                </FieldGrid>
                            </CardContent>
                        </Card>

                        {employee.reports && employee.reports.length > 0 ? (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        Bawahan langsung (
                                        {employee.reports.length})
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                    {employee.reports.map((report) => (
                                        <Link
                                            className="rounded-md border p-3 text-sm hover:bg-muted"
                                            href={`/employees/${report.id}`}
                                            key={report.id}
                                        >
                                            <p className="font-medium">
                                                {report.user.name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {report.employee_number}
                                            </p>
                                        </Link>
                                    ))}
                                </CardContent>
                            </Card>
                        ) : null}

                        {canUpdate ? (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        Ubah data kepegawaian
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <Form
                                        action={`/employees/${employee.id}`}
                                        className="grid gap-3 md:grid-cols-3"
                                        method="put"
                                    >
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="edit-name">
                                                Nama
                                            </Label>
                                            <Input
                                                defaultValue={
                                                    employee.user.name
                                                }
                                                id="edit-name"
                                                name="name"
                                                required
                                            />
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="edit-email">
                                                Email
                                            </Label>
                                            <Input
                                                defaultValue={
                                                    employee.user.email
                                                }
                                                id="edit-email"
                                                name="email"
                                                required
                                                type="email"
                                            />
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="edit-phone">
                                                Telepon
                                            </Label>
                                            <Input
                                                defaultValue={employee.phone}
                                                id="edit-phone"
                                                name="phone"
                                            />
                                        </div>
                                        {[
                                            [
                                                'department_id',
                                                'Departemen',
                                                departments,
                                                employee.department_id,
                                            ],
                                            [
                                                'position_id',
                                                'Posisi',
                                                positions,
                                                employee.position_id,
                                            ],
                                            [
                                                'location_id',
                                                'Lokasi',
                                                locations,
                                                employee.location_id,
                                            ],
                                            [
                                                'employment_type_id',
                                                'Tipe kepegawaian',
                                                employmentTypes,
                                                employee.employment_type_id,
                                            ],
                                        ].map(
                                            ([name, label, options, value]) => (
                                                <div
                                                    className="grid gap-1.5"
                                                    key={name as string}
                                                >
                                                    <Label
                                                        htmlFor={`edit-${name}`}
                                                    >
                                                        {label as string}
                                                    </Label>
                                                    <select
                                                        className="h-9 rounded-md border bg-background px-3 text-sm"
                                                        defaultValue={
                                                            (value as number) ??
                                                            ''
                                                        }
                                                        id={`edit-${name}`}
                                                        name={name as string}
                                                    >
                                                        <option value="">
                                                            —
                                                        </option>
                                                        {(
                                                            options as Option[]
                                                        ).map((option) => (
                                                            <option
                                                                key={option.id}
                                                                value={
                                                                    option.id
                                                                }
                                                            >
                                                                {option.name}
                                                            </option>
                                                        ))}
                                                    </select>
                                                </div>
                                            ),
                                        )}
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="edit-manager">
                                                Manager
                                            </Label>
                                            <select
                                                className="h-9 rounded-md border bg-background px-3 text-sm"
                                                defaultValue={
                                                    employee.manager_id ?? ''
                                                }
                                                id="edit-manager"
                                                name="manager_id"
                                            >
                                                <option value="">
                                                    Tanpa manager
                                                </option>
                                                {managers.map((manager) => (
                                                    <option
                                                        key={manager.id}
                                                        value={manager.id}
                                                    >
                                                        {manager.user.name}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="edit-joined">
                                                Tanggal bergabung
                                            </Label>
                                            <Input
                                                defaultValue={
                                                    employee.joined_at
                                                }
                                                id="edit-joined"
                                                name="joined_at"
                                                required
                                                type="date"
                                            />
                                        </div>
                                        {canSeeSensitive ? (
                                            <div className="grid gap-1.5">
                                                <Label htmlFor="edit-salary">
                                                    Gaji pokok
                                                </Label>
                                                <CurrencyInput
                                                    defaultValue={
                                                        employee.basic_salary
                                                    }
                                                    id="edit-salary"
                                                    name="basic_salary"
                                                    required
                                                />
                                            </div>
                                        ) : null}
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="edit-role">
                                                Peran
                                            </Label>
                                            <select
                                                className="h-9 rounded-md border bg-background px-3 text-sm"
                                                defaultValue={
                                                    employee.user.role
                                                }
                                                id="edit-role"
                                                name="role"
                                            >
                                                {[
                                                    'super_admin',
                                                    'hr_admin',
                                                    'manager',
                                                    'employee',
                                                ].map((role) => (
                                                    <option
                                                        key={role}
                                                        value={role}
                                                    >
                                                        {role}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="edit-status">
                                                Status kepegawaian
                                            </Label>
                                            <select
                                                className="h-9 rounded-md border bg-background px-3 text-sm"
                                                defaultValue={
                                                    employee.employment_status ??
                                                    'active'
                                                }
                                                id="edit-status"
                                                name="employment_status"
                                            >
                                                {[
                                                    ['active', 'Aktif'],
                                                    ['probation', 'Probation'],
                                                    [
                                                        'on_leave',
                                                        'Cuti panjang',
                                                    ],
                                                    ['suspended', 'Diskors'],
                                                    ['resigned', 'Resign'],
                                                    [
                                                        'terminated',
                                                        'Diberhentikan',
                                                    ],
                                                ].map(([value, label]) => (
                                                    <option
                                                        key={value}
                                                        value={value}
                                                    >
                                                        {label}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        <div className="md:col-span-3">
                                            <Button type="submit">
                                                Simpan perubahan
                                            </Button>
                                        </div>
                                    </Form>
                                </CardContent>
                            </Card>
                        ) : null}
                    </TabsContent>

                    <TabsContent
                        className="mt-4 flex flex-col gap-4"
                        value="attendance"
                    >
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Ringkasan bulan ini
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-wrap gap-3">
                                {Object.entries(summaries.attendance.thisMonth)
                                    .length > 0 ? (
                                    Object.entries(
                                        summaries.attendance.thisMonth,
                                    ).map(([status, total]) => (
                                        <div
                                            className="rounded-md border px-3 py-2"
                                            key={status}
                                        >
                                            <p className="text-xs text-muted-foreground capitalize">
                                                {status}
                                            </p>
                                            <p className="text-lg font-semibold tabular-nums">
                                                {total}
                                            </p>
                                        </div>
                                    ))
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        Belum ada data kehadiran bulan ini.
                                    </p>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    10 kehadiran terakhir
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left text-xs text-muted-foreground">
                                            <th className="py-2 pr-3">
                                                Tanggal
                                            </th>
                                            <th className="py-2 pr-3">
                                                Status
                                            </th>
                                            <th className="py-2 pr-3">Masuk</th>
                                            <th className="py-2 pr-3">
                                                Pulang
                                            </th>
                                            <th className="py-2 pr-3">
                                                Menit kerja
                                            </th>
                                            <th className="py-2">Telat</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {summaries.attendance.recent.map(
                                            (row) => (
                                                <tr
                                                    className="border-b last:border-0"
                                                    key={row.id}
                                                >
                                                    <td className="py-2 pr-3">
                                                        {row.date}
                                                    </td>
                                                    <td className="py-2 pr-3 capitalize">
                                                        {row.status}
                                                    </td>
                                                    <td className="py-2 pr-3">
                                                        {row.checked_in_at?.slice(
                                                            11,
                                                            16,
                                                        ) ?? '—'}
                                                    </td>
                                                    <td className="py-2 pr-3">
                                                        {row.checked_out_at?.slice(
                                                            11,
                                                            16,
                                                        ) ?? '—'}
                                                    </td>
                                                    <td className="py-2 pr-3 tabular-nums">
                                                        {row.worked_minutes}
                                                    </td>
                                                    <td className="py-2 tabular-nums">
                                                        {row.late_minutes}
                                                    </td>
                                                </tr>
                                            ),
                                        )}
                                    </tbody>
                                </table>
                                {summaries.attendance.recent.length === 0 ? (
                                    <p className="py-4 text-sm text-muted-foreground">
                                        Belum ada catatan kehadiran.
                                    </p>
                                ) : null}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent
                        className="mt-4 flex flex-col gap-4"
                        value="leave"
                    >
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Saldo cuti tahun ini
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                {summaries.leave.balances.map((balance) => (
                                    <div
                                        className="rounded-md border p-3"
                                        key={balance.id}
                                    >
                                        <p className="flex items-center gap-2 text-sm font-medium">
                                            <span
                                                aria-hidden
                                                className="size-2.5 rounded-full"
                                                style={{
                                                    background:
                                                        balance.leave_type
                                                            ?.color ??
                                                        '#2563eb',
                                                }}
                                            />
                                            {balance.leave_type?.name}
                                        </p>
                                        <p className="mt-1 text-lg font-semibold tabular-nums">
                                            {balance.remaining} hari tersisa
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            Hak {Number(balance.entitled)} ·
                                            terpakai {Number(balance.used)} ·
                                            menunggu {Number(balance.pending)}
                                        </p>
                                    </div>
                                ))}
                                {summaries.leave.balances.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        Belum ada saldo cuti.
                                    </p>
                                ) : null}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Riwayat pengajuan
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-2">
                                {summaries.leave.recent.map((row) => (
                                    <div
                                        className="flex flex-wrap items-center justify-between gap-2 rounded-md border p-3"
                                        key={row.id}
                                    >
                                        <div>
                                            <p className="text-sm font-medium">
                                                {row.leave_type?.name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {row.request_number
                                                    ? `${row.request_number} · `
                                                    : ''}
                                                {row.start_date} →{' '}
                                                {row.end_date}
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <span className="text-sm tabular-nums">
                                                {Number(row.days)} hari
                                            </span>
                                            <Badge variant="secondary">
                                                {row.status}
                                            </Badge>
                                        </div>
                                    </div>
                                ))}
                                {summaries.leave.recent.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        Belum ada pengajuan cuti.
                                    </p>
                                ) : null}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {canSeeSensitive ? (
                        <TabsContent
                            className="mt-4 flex flex-col gap-4"
                            value="compensation"
                        >
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        Kompensasi dan bank
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <FieldGrid>
                                        <Field
                                            label="Gaji pokok"
                                            value={
                                                employee.basic_salary
                                                    ? currency.format(
                                                          Number(
                                                              employee.basic_salary,
                                                          ),
                                                      )
                                                    : undefined
                                            }
                                        />
                                        <Field
                                            label="Bank"
                                            value={employee.bank_name}
                                        />
                                        <Field
                                            label="Nomor rekening"
                                            value={employee.bank_account}
                                        />
                                        <Field
                                            label="Atas nama"
                                            value={employee.bank_account_holder}
                                        />
                                        <Field
                                            label="NPWP"
                                            value={employee.tax_number}
                                        />
                                    </FieldGrid>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        Riwayat gaji
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="flex flex-col gap-2">
                                    {(employee.salary_histories ?? []).map(
                                        (row) => (
                                            <div
                                                className="flex items-center justify-between gap-3 rounded-md border p-3 text-sm"
                                                key={row.id}
                                            >
                                                <span>
                                                    {row.effective_from} →{' '}
                                                    {row.effective_to ??
                                                        'sekarang'}
                                                </span>
                                                <span className="font-medium tabular-nums">
                                                    {currency.format(
                                                        Number(row.amount),
                                                    )}
                                                </span>
                                            </div>
                                        ),
                                    )}
                                    {(employee.salary_histories ?? [])
                                        .length === 0 ? (
                                        <p className="text-sm text-muted-foreground">
                                            Belum ada riwayat gaji.
                                        </p>
                                    ) : null}
                                </CardContent>
                            </Card>

                            {canUpdate ? (
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="text-base">
                                            Ubah data bank & pajak
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <Form
                                            action={`/employees/${employee.id}/profile`}
                                            className="grid gap-3 md:grid-cols-3"
                                            method="put"
                                        >
                                            {({ errors, processing }) => (
                                                <>
                                                    <div className="grid gap-1.5">
                                                        <Label htmlFor="c-bank-name">
                                                            Bank
                                                        </Label>
                                                        <Input
                                                            defaultValue={
                                                                employee.bank_name ??
                                                                ''
                                                            }
                                                            id="c-bank-name"
                                                            name="bank_name"
                                                        />
                                                    </div>
                                                    <div className="grid gap-1.5">
                                                        <Label htmlFor="c-bank-account">
                                                            Nomor rekening
                                                        </Label>
                                                        <Input
                                                            defaultValue={
                                                                employee.bank_account ??
                                                                ''
                                                            }
                                                            id="c-bank-account"
                                                            name="bank_account"
                                                        />
                                                    </div>
                                                    <div className="grid gap-1.5">
                                                        <Label htmlFor="c-bank-holder">
                                                            Atas nama
                                                        </Label>
                                                        <Input
                                                            defaultValue={
                                                                employee.bank_account_holder ??
                                                                ''
                                                            }
                                                            id="c-bank-holder"
                                                            name="bank_account_holder"
                                                        />
                                                    </div>
                                                    <div className="grid gap-1.5">
                                                        <Label htmlFor="c-tax-number">
                                                            NPWP
                                                        </Label>
                                                        <Input
                                                            defaultValue={
                                                                employee.tax_number ??
                                                                ''
                                                            }
                                                            id="c-tax-number"
                                                            name="tax_number"
                                                        />
                                                    </div>
                                                    <Button
                                                        className="md:col-span-3 md:justify-self-start"
                                                        disabled={processing}
                                                    >
                                                        Simpan data bank
                                                    </Button>
                                                    {Object.keys(errors)
                                                        .length > 0 ? (
                                                        <p className="text-sm text-destructive md:col-span-3">
                                                            Periksa kembali
                                                            data formulir.
                                                        </p>
                                                    ) : null}
                                                </>
                                            )}
                                        </Form>
                                        <p className="mt-3 text-sm text-muted-foreground">
                                            Untuk mengubah gaji pokok, buka
                                            tab Kepegawaian.
                                        </p>
                                    </CardContent>
                                </Card>
                            ) : null}

                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        Slip gaji terbit
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="flex flex-col gap-2">
                                    {summaries.payroll.map((record) => (
                                        <Link
                                            className="flex items-center justify-between gap-3 rounded-md border p-3 text-sm hover:bg-muted"
                                            href={`/payslips/${record.id}`}
                                            key={record.id}
                                        >
                                            <span>{record.period?.name}</span>
                                            <span className="font-medium tabular-nums">
                                                {currency.format(
                                                    Number(record.net_salary),
                                                )}
                                            </span>
                                        </Link>
                                    ))}
                                    {summaries.payroll.length === 0 ? (
                                        <p className="text-sm text-muted-foreground">
                                            Belum ada slip gaji terbit.
                                        </p>
                                    ) : null}
                                </CardContent>
                            </Card>
                        </TabsContent>
                    ) : null}

                    <TabsContent
                        className="mt-4 flex flex-col gap-4"
                        value="documents"
                    >
                        {canUpdate ? (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        Unggah dokumen
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <Form
                                        action={`/employees/${employee.id}/documents`}
                                        className="grid gap-3 md:grid-cols-4"
                                        method="post"
                                    >
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="doc-name">
                                                Nama
                                            </Label>
                                            <Input
                                                id="doc-name"
                                                name="name"
                                                required
                                            />
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="doc-category">
                                                Kategori
                                            </Label>
                                            <select
                                                className="h-9 rounded-md border bg-background px-3 text-sm"
                                                id="doc-category"
                                                name="category"
                                            >
                                                {[
                                                    'identity',
                                                    'contract',
                                                    'education',
                                                    'certification',
                                                    'tax',
                                                    'bank',
                                                    'other',
                                                ].map((c) => (
                                                    <option key={c} value={c}>
                                                        {c}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label htmlFor="doc-file">
                                                Berkas
                                            </Label>
                                            <Input
                                                accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                                id="doc-file"
                                                name="file"
                                                required
                                                type="file"
                                            />
                                        </div>
                                        <div className="flex items-end">
                                            <Button type="submit">
                                                <Upload />
                                                Unggah
                                            </Button>
                                        </div>
                                    </Form>
                                </CardContent>
                            </Card>
                        ) : null}

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Dokumen ({employee.documents.length})
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-2">
                                {employee.documents.map((document) => (
                                    <div
                                        className="flex flex-wrap items-center justify-between gap-3 rounded-md border p-3"
                                        key={document.id}
                                    >
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium">
                                                {document.title ||
                                                    document.name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {document.category ?? 'other'} ·{' '}
                                                {Math.round(
                                                    document.size / 1024,
                                                )}{' '}
                                                KB
                                                {document.expires_at
                                                    ? ` · kedaluwarsa ${document.expires_at}`
                                                    : ''}
                                                {document.uploader
                                                    ? ` · oleh ${document.uploader.name}`
                                                    : ''}
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Button
                                                asChild
                                                size="sm"
                                                variant="outline"
                                            >
                                                <a
                                                    href={`/employee-documents/${document.id}`}
                                                >
                                                    <Download />
                                                    Unduh
                                                </a>
                                            </Button>
                                            {canUpdate ? (
                                                <Button
                                                    onClick={() =>
                                                        router.delete(
                                                            `/employee-documents/${document.id}`,
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                    size="sm"
                                                    variant="outline"
                                                >
                                                    <Trash2 />
                                                </Button>
                                            ) : null}
                                        </div>
                                    </div>
                                ))}
                                {employee.documents.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        Belum ada dokumen.
                                    </p>
                                ) : null}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent className="mt-4" value="activity">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Riwayat aktivitas
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-3">
                                {timeline.map((entry) => (
                                    <div
                                        className="flex gap-3 border-b pb-3 last:border-0 last:pb-0"
                                        key={entry.id}
                                    >
                                        <div
                                            aria-hidden
                                            className="mt-1.5 size-2 shrink-0 rounded-full bg-primary"
                                        />
                                        <div className="min-w-0">
                                            <p className="text-sm">
                                                {entry.description ??
                                                    entry.event}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {entry.created_at
                                                    ?.slice(0, 16)
                                                    .replace('T', ' ')}
                                                {entry.user
                                                    ? ` · ${entry.user.name}`
                                                    : ' · sistem'}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                                {timeline.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        Belum ada aktivitas tercatat.
                                    </p>
                                ) : null}
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>

            <Dialog
                onOpenChange={setConfirmingDeactivate}
                open={confirmingDeactivate}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Nonaktifkan karyawan</DialogTitle>
                        <DialogDescription>
                            {employee.user.name} tidak akan bisa masuk lagi dan
                            bawahan langsungnya akan dilepas. Riwayat data tetap
                            tersimpan.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-1.5">
                        <Label htmlFor="deactivate-status">Status keluar</Label>
                        <select
                            id="deactivate-status"
                            className="h-9 rounded-md border bg-background px-3 text-sm"
                            value={deactivateStatus}
                            onChange={(event) =>
                                setDeactivateStatus(event.target.value)
                            }
                        >
                            <option value="resigned">Resign</option>
                            <option value="terminated">Diberhentikan</option>
                        </select>
                    </div>
                    <div className="grid gap-1.5">
                        <Label htmlFor="deactivate-reason">Alasan</Label>
                        <Input
                            id="deactivate-reason"
                            onChange={(event) =>
                                setDeactivateReason(event.target.value)
                            }
                            placeholder="Mengundurkan diri, kontrak berakhir, ..."
                            value={deactivateReason}
                        />
                    </div>
                    <DialogFooter>
                        <Button
                            onClick={() => setConfirmingDeactivate(false)}
                            variant="outline"
                        >
                            Batal
                        </Button>
                        <Button
                            disabled={deactivateReason.trim().length === 0}
                            onClick={deactivate}
                            variant="destructive"
                        >
                            Nonaktifkan
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
