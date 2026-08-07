import { Head, Link, router } from '@inertiajs/react';
import { ArrowDown, ArrowUp, PenLine, Plus, Search } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Record_ = Record<string, unknown> & { id: number };

type Props = {
    entity: string;
    label: string;
    entities: Array<{ key: string; label: string }>;
    sortable: string[];
    records: {
        data: Record_[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: { search: string; sort: string; direction: string };
    departments: Array<{ id: number; name: string }>;
    canManage: boolean;
};

type FieldSpec = {
    name: string;
    label: string;
    type?:
        | 'text'
        | 'number'
        | 'currency'
        | 'date'
        | 'select'
        | 'checkbox'
        | 'color';
    required?: boolean;
    options?: 'departments';
};

/** Columns rendered in the table, and fields offered in the create/edit dialog. */
const SCHEMA: Record<
    string,
    { columns: Array<[string, string]>; fields: FieldSpec[] }
> = {
    departments: {
        columns: [
            ['code', 'Kode'],
            ['name', 'Nama'],
            ['employees_count', 'Karyawan'],
            ['is_active', 'Aktif'],
        ],
        fields: [
            { name: 'code', label: 'Kode', required: true },
            { name: 'name', label: 'Nama', required: true },
            { name: 'description', label: 'Deskripsi' },
            { name: 'is_active', label: 'Aktif', type: 'checkbox' },
        ],
    },
    positions: {
        columns: [
            ['code', 'Kode'],
            ['name', 'Nama'],
            ['level', 'Level'],
            ['employees_count', 'Karyawan'],
            ['is_active', 'Aktif'],
        ],
        fields: [
            {
                name: 'department_id',
                label: 'Departemen',
                type: 'select',
                options: 'departments',
                required: true,
            },
            { name: 'code', label: 'Kode' },
            { name: 'name', label: 'Nama', required: true },
            { name: 'level', label: 'Level', type: 'number', required: true },
            { name: 'min_salary', label: 'Gaji minimum', type: 'currency' },
            { name: 'max_salary', label: 'Gaji maksimum', type: 'currency' },
            { name: 'is_active', label: 'Aktif', type: 'checkbox' },
        ],
    },
    locations: {
        columns: [
            ['code', 'Kode'],
            ['name', 'Nama'],
            ['city', 'Kota'],
            ['timezone', 'Zona waktu'],
            ['is_active', 'Aktif'],
        ],
        fields: [
            { name: 'code', label: 'Kode' },
            { name: 'name', label: 'Nama', required: true },
            { name: 'address', label: 'Alamat' },
            { name: 'city', label: 'Kota' },
            { name: 'province', label: 'Provinsi' },
            { name: 'timezone', label: 'Zona waktu', required: true },
            { name: 'is_active', label: 'Aktif', type: 'checkbox' },
        ],
    },
    'employment-types': {
        columns: [
            ['code', 'Kode'],
            ['name', 'Nama'],
            ['employees_count', 'Karyawan'],
            ['is_active', 'Aktif'],
        ],
        fields: [
            { name: 'code', label: 'Kode' },
            { name: 'name', label: 'Nama', required: true },
            { name: 'description', label: 'Deskripsi' },
            { name: 'is_active', label: 'Aktif', type: 'checkbox' },
        ],
    },
    'leave-types': {
        columns: [
            ['code', 'Kode'],
            ['name', 'Nama'],
            ['annual_quota', 'Kuota'],
            ['is_paid', 'Berbayar'],
            ['is_active', 'Aktif'],
        ],
        fields: [
            { name: 'code', label: 'Kode' },
            { name: 'name', label: 'Nama', required: true },
            {
                name: 'annual_quota',
                label: 'Kuota tahunan',
                type: 'number',
                required: true,
            },
            {
                name: 'min_notice_days',
                label: 'Minimal pemberitahuan (hari)',
                type: 'number',
                required: true,
            },
            {
                name: 'max_consecutive_days',
                label: 'Maksimal berturut-turut',
                type: 'number',
            },
            { name: 'color', label: 'Warna', type: 'color', required: true },
            { name: 'is_paid', label: 'Berbayar', type: 'checkbox' },
            {
                name: 'requires_attachment',
                label: 'Wajib lampiran',
                type: 'checkbox',
            },
            {
                name: 'allows_negative_balance',
                label: 'Boleh saldo minus',
                type: 'checkbox',
            },
            {
                name: 'carry_forward_enabled',
                label: 'Bisa dibawa ke tahun depan',
                type: 'checkbox',
            },
            { name: 'is_active', label: 'Aktif', type: 'checkbox' },
        ],
    },
    holidays: {
        columns: [
            ['date', 'Tanggal'],
            ['name', 'Nama'],
            ['is_recurring', 'Berulang'],
            ['is_active', 'Aktif'],
        ],
        fields: [
            { name: 'date', label: 'Tanggal', type: 'date', required: true },
            { name: 'name', label: 'Nama', required: true },
            { name: 'description', label: 'Deskripsi' },
            {
                name: 'is_recurring',
                label: 'Berulang tiap tahun',
                type: 'checkbox',
            },
            { name: 'is_active', label: 'Aktif', type: 'checkbox' },
        ],
    },
};

/** Inertia only accepts primitives in a request payload. */
type FormValue = string | number | boolean | null;
type FormValues = Record<string, FormValue>;

const DEFAULTS: FormValues = {
    is_active: true,
    level: 1,
    annual_quota: 12,
    min_notice_days: 0,
    color: '#2563eb',
    timezone: 'Asia/Makassar',
};

function renderCell(value: unknown) {
    if (typeof value === 'boolean') {
        return (
            <Badge variant={value ? 'default' : 'secondary'}>
                {value ? 'Ya' : 'Tidak'}
            </Badge>
        );
    }

    if (value === null || value === undefined || value === '') {
        return <span className="text-muted-foreground">—</span>;
    }

    return String(value);
}

export default function Organization({
    entity,
    label,
    entities,
    sortable,
    records,
    filters,
    departments,
    canManage,
}: Props) {
    const schema = SCHEMA[entity];
    const [search, setSearch] = useState(filters.search);
    const [editing, setEditing] = useState<Record_ | null>(null);
    const [creating, setCreating] = useState(false);
    const [form, setForm] = useState<FormValues>({});

    const navigate = (params: Record<string, string | undefined>) =>
        router.get(
            `/organization/${entity}`,
            { ...filters, ...params },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );

    const openCreate = () => {
        setForm({ ...DEFAULTS });
        setCreating(true);
    };

    const openEdit = (record: Record_) => {
        const values: FormValues = {};

        for (const field of schema.fields) {
            values[field.name] =
                (record[field.name] as FormValue) ?? DEFAULTS[field.name] ?? '';
        }

        setForm(values);
        setEditing(record);
    };

    const submit = () => {
        const payload = { ...form };

        if (editing) {
            router.put(`/organization/${entity}/${editing.id}`, payload, {
                preserveScroll: true,
                onSuccess: () => setEditing(null),
            });

            return;
        }

        router.post(`/organization/${entity}`, payload, {
            preserveScroll: true,
            onSuccess: () => setCreating(false),
        });
    };

    const dialogOpen = creating || editing !== null;

    return (
        <>
            <Head title={label} />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Data organisasi</h1>
                    <p className="text-muted-foreground">
                        Kelola struktur dan data acuan perusahaan.
                    </p>
                </div>

                <nav className="flex flex-wrap gap-2">
                    {entities.map((item) => (
                        <Button
                            asChild
                            key={item.key}
                            size="sm"
                            variant={
                                item.key === entity ? 'default' : 'outline'
                            }
                        >
                            <Link href={`/organization/${item.key}`}>
                                {item.label}
                            </Link>
                        </Button>
                    ))}
                </nav>

                <Card>
                    <CardHeader className="flex-row flex-wrap items-center justify-between gap-3 space-y-0">
                        <CardTitle className="text-base">
                            {label}
                            <span className="ml-2 text-sm font-normal text-muted-foreground">
                                {records.total} data
                            </span>
                        </CardTitle>
                        <div className="flex flex-wrap items-center gap-2">
                            <form
                                className="flex items-center gap-2"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    navigate({ search: search || undefined });
                                }}
                            >
                                <div className="relative">
                                    <Search className="absolute top-2.5 left-2.5 size-4 text-muted-foreground" />
                                    <Input
                                        aria-label="Cari"
                                        className="w-56 pl-8"
                                        onChange={(event) =>
                                            setSearch(event.target.value)
                                        }
                                        placeholder="Cari..."
                                        value={search}
                                    />
                                </div>
                                <Button
                                    size="sm"
                                    type="submit"
                                    variant="outline"
                                >
                                    Cari
                                </Button>
                                {filters.search ? (
                                    <Button
                                        onClick={() => {
                                            setSearch('');
                                            navigate({ search: undefined });
                                        }}
                                        size="sm"
                                        type="button"
                                        variant="ghost"
                                    >
                                        Reset
                                    </Button>
                                ) : null}
                            </form>
                            {canManage ? (
                                <Button onClick={openCreate} size="sm">
                                    <Plus />
                                    Tambah
                                </Button>
                            ) : null}
                        </div>
                    </CardHeader>

                    <CardContent className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left text-xs text-muted-foreground">
                                    {schema.columns.map(([key, header]) => (
                                        <th className="py-2 pr-3" key={key}>
                                            {sortable.includes(key) ? (
                                                <button
                                                    className="inline-flex items-center gap-1 hover:text-foreground"
                                                    onClick={() =>
                                                        navigate({
                                                            sort: key,
                                                            direction:
                                                                filters.sort ===
                                                                    key &&
                                                                filters.direction ===
                                                                    'asc'
                                                                    ? 'desc'
                                                                    : 'asc',
                                                        })
                                                    }
                                                    type="button"
                                                >
                                                    {header}
                                                    {filters.sort === key ? (
                                                        filters.direction ===
                                                        'asc' ? (
                                                            <ArrowUp className="size-3" />
                                                        ) : (
                                                            <ArrowDown className="size-3" />
                                                        )
                                                    ) : null}
                                                </button>
                                            ) : (
                                                header
                                            )}
                                        </th>
                                    ))}
                                    {canManage ? (
                                        <th className="py-2">Aksi</th>
                                    ) : null}
                                </tr>
                            </thead>
                            <tbody>
                                {records.data.map((record) => (
                                    <tr
                                        className="border-b last:border-0"
                                        key={record.id}
                                    >
                                        {schema.columns.map(([key]) => (
                                            <td className="py-2 pr-3" key={key}>
                                                {renderCell(record[key])}
                                            </td>
                                        ))}
                                        {canManage ? (
                                            <td className="py-2">
                                                <Button
                                                    onClick={() =>
                                                        openEdit(record)
                                                    }
                                                    size="sm"
                                                    variant="outline"
                                                >
                                                    <PenLine />
                                                    Ubah
                                                </Button>
                                            </td>
                                        ) : null}
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {records.data.length === 0 ? (
                            <p className="py-8 text-center text-sm text-muted-foreground">
                                {filters.search
                                    ? `Tidak ada hasil untuk "${filters.search}".`
                                    : 'Belum ada data.'}
                            </p>
                        ) : null}

                        {records.links.length > 3 ? (
                            <div className="flex flex-wrap items-center justify-between gap-3 pt-4">
                                <p className="text-xs text-muted-foreground">
                                    Menampilkan {records.from ?? 0}–
                                    {records.to ?? 0} dari {records.total}
                                </p>
                                <div className="flex flex-wrap gap-1">
                                    {records.links.map((link) => (
                                        <Button
                                            disabled={!link.url}
                                            key={link.label}
                                            onClick={() =>
                                                link.url && router.get(link.url)
                                            }
                                            size="sm"
                                            variant={
                                                link.active
                                                    ? 'default'
                                                    : 'outline'
                                            }
                                        >
                                            <span
                                                dangerouslySetInnerHTML={{
                                                    __html: link.label,
                                                }}
                                            />
                                        </Button>
                                    ))}
                                </div>
                            </div>
                        ) : null}
                    </CardContent>
                </Card>
            </div>

            <Dialog
                onOpenChange={(open) => {
                    if (!open) {
                        setCreating(false);
                        setEditing(null);
                    }
                }}
                open={dialogOpen}
            >
                <DialogContent className="max-h-[85vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>
                            {editing
                                ? `Ubah ${label.toLowerCase()}`
                                : `Tambah ${label.toLowerCase()}`}
                        </DialogTitle>
                    </DialogHeader>

                    <div className="grid gap-3">
                        {schema.fields.map((field) => {
                            const id = `field-${field.name}`;
                            const value = form[field.name];

                            if (field.type === 'checkbox') {
                                return (
                                    <div
                                        className="flex items-center gap-2"
                                        key={field.name}
                                    >
                                        <Checkbox
                                            checked={Boolean(value)}
                                            id={id}
                                            onCheckedChange={(checked) =>
                                                setForm({
                                                    ...form,
                                                    [field.name]:
                                                        checked === true,
                                                })
                                            }
                                        />
                                        <Label htmlFor={id}>
                                            {field.label}
                                        </Label>
                                    </div>
                                );
                            }

                            return (
                                <div className="grid gap-1.5" key={field.name}>
                                    <Label htmlFor={id}>
                                        {field.label}
                                        {field.required ? ' *' : ''}
                                    </Label>
                                    {field.type === 'select' ? (
                                        <select
                                            className="h-9 rounded-md border bg-background px-3 text-sm"
                                            id={id}
                                            onChange={(event) =>
                                                setForm({
                                                    ...form,
                                                    [field.name]:
                                                        event.target.value,
                                                })
                                            }
                                            value={String(value ?? '')}
                                        >
                                            <option value="">—</option>
                                            {departments.map((option) => (
                                                <option
                                                    key={option.id}
                                                    value={option.id}
                                                >
                                                    {option.name}
                                                </option>
                                            ))}
                                        </select>
                                    ) : field.type === 'currency' ? (
                                        <div className="relative">
                                            <span className="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm text-muted-foreground">
                                                Rp
                                            </span>
                                            <Input
                                                className="pl-9"
                                                id={id}
                                                inputMode="numeric"
                                                onChange={(event) =>
                                                    setForm({
                                                        ...form,
                                                        [field.name]:
                                                            event.target.value.replace(
                                                                /\D/g,
                                                                '',
                                                            ),
                                                    })
                                                }
                                                value={
                                                    value !== '' &&
                                                    value !== undefined &&
                                                    Number.isFinite(
                                                        Number(value),
                                                    )
                                                        ? Math.round(
                                                              Number(value),
                                                          ).toLocaleString(
                                                              'id-ID',
                                                          )
                                                        : ''
                                                }
                                            />
                                        </div>
                                    ) : (
                                        <Input
                                            id={id}
                                            onChange={(event) =>
                                                setForm({
                                                    ...form,
                                                    [field.name]:
                                                        event.target.value,
                                                })
                                            }
                                            type={
                                                field.type === 'color'
                                                    ? 'color'
                                                    : (field.type ?? 'text')
                                            }
                                            value={String(value ?? '')}
                                        />
                                    )}
                                </div>
                            );
                        })}
                    </div>

                    <DialogFooter>
                        <Button
                            onClick={() => {
                                setCreating(false);
                                setEditing(null);
                            }}
                            variant="outline"
                        >
                            Batal
                        </Button>
                        <Button onClick={submit}>Simpan</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
