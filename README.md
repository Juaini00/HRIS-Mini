# NusaHR

NusaHR adalah portfolio **Human Resource Information System** berbasis Laravel, Inertia React, TypeScript, Tailwind CSS, dan PostgreSQL. Aplikasi menyediakan autentikasi internal, data organisasi dan karyawan, pengajuan/persetujuan cuti, presensi, payroll sederhana, pengumuman, laporan CSV, audit-ready schema, serta demo data.

## Modul dan peran

- **Super Admin / HR Admin:** data karyawan, cuti, presensi, payroll, pengumuman, dan laporan.
- **Manager:** dashboard, presensi, cuti pribadi, dan persetujuan bawahan langsung.
- **Employee:** dashboard, presensi, pengajuan cuti, pengumuman, dan payslip yang sudah dipublikasikan.
- Registrasi publik dinonaktifkan; akun dibuat HR secara transaksional.

## Instalasi lokal

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan storage:link
php artisan migrate --seed
npm run build
php artisan serve
```

Jalankan worker dan scheduler di terminal terpisah:

```bash
php artisan queue:work
php artisan schedule:work
```

### Neon PostgreSQL

Isi `DATABASE_URL` hanya di `.env` lokal/secret manager. URL Neon harus menggunakan `sslmode=require`; jangan pernah memasukkannya ke Git. Pastikan ekstensi PHP `pdo_pgsql` tersedia, kemudian jalankan `php artisan migrate --seed`. Konfigurasi `DB_HOST` dan variabel PostgreSQL biasa tetap tersedia sebagai fallback.

### Docker PostgreSQL dan Mailpit

```bash
docker compose up -d postgres mailpit
```

Gunakan `DB_HOST=postgres`, `DB_DATABASE=nusahr`, `DB_USERNAME=nusahr`, dan `DB_PASSWORD=nusahr` bila aplikasi juga dijalankan dalam jaringan Compose. Mailpit tersedia pada `http://localhost:8025`.

## Demo

Semua akun menggunakan kata sandi `NusaHR123!`:

| Peran | Email |
|---|---|
| Super Admin | `admin@nusahr.test` |
| HR Admin | `hr@nusahr.test` |
| Manager | `manager@nusahr.test` |
| Employee | `employee@nusahr.test` |

Ubah kredensial demo sebelum penggunaan non-lokal.

## Quality checks

```bash
php artisan test --compact
vendor/bin/pint --format agent
composer types:check
npm run types:check
npm run lint:check
npm run format:check
npm run build
```

## Arsitektur dan keamanan

Controller tetap tipis dan workflow kritis berada pada action yang menggunakan transaction serta row lock. Otorisasi selalu dilakukan server-side. Data gaji/bank disembunyikan dari serialisasi model, dokumen dirancang melalui storage privat, dan kredensial hanya berasal dari environment. Lihat [`docs/architecture.md`](docs/architecture.md), [`docs/business-rules.md`](docs/business-rules.md), dan dokumentasi lainnya di `docs/`.

## Keterbatasan

Payroll adalah kalkulasi sederhana berbasis gaji pokok; pajak dan BPJS belum dihitung. UI koreksi presensi, pengelolaan dokumen, settings, serta audit viewer belum tersedia meskipun tabel fondasinya disediakan. PDF payslip belum diimplementasikan; data payroll ditampilkan dalam aplikasi. Lisensi: MIT.
