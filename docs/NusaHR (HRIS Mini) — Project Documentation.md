# NusaHR \- Human Resource Information System

## Dokumentasi Proyek / Portfolio

**Repository:** [https://github.com/Juaini00/HRIS-Mini](https://github.com/Juaini00/HRIS-Mini)

# 1\. Ringkasan Proyek

NusaHR adalah aplikasi Human Resource Information System (HRIS) berbasis web yang dirancang sebagai portfolio aplikasi produksi. Aplikasi ini membantu perusahaan mengelola data karyawan, struktur organisasi, cuti, kehadiran, payroll sederhana, pengumuman, dokumen privat, notifikasi, laporan, pengaturan, dan riwayat audit dalam satu sistem.

# 2\. Latar Belakang dan Masalah yang Diselesaikan

Administrasi SDM (Sumber Daya Manusia)  sering tersebar di spreadsheet, percakapan, dan proses manual. Kondisi ini meningkatkan risiko data tidak konsisten, approval cuti terlambat, perhitungan kehadiran tidak akurat, serta akses data sensitif yang tidak terkontrol. NusaHR memusatkan alur tersebut dengan aturan bisnis, audit trail, dan pembatasan akses berbasis peran.

# 3\. Tujuan

Tujuan utama proyek ini adalah membangun **HRIS** modern yang aman, mudah digunakan, dan realistis untuk kebutuhan operasional dasar HR. Sistem memprioritaskan integritas data, workflow yang jelas, pengalaman pengguna responsif, dan fondasi yang siap dikembangkan untuk kebutuhan enterprise.

# 4\. Teknologi yang Digunakan

Backend:

* Laravel 13 dan PHP 8.3+  
* PostgreSQL atau Neon PostgreSQL

Frontend:

* React 19  
* TypeScript  
* Inertia 3  
* Tailwind CSS 4  
* Shadcn/Radix

Tools:

* Vite  
* Docker Compose  
* Pest  
* Larastan  
* Pint, ESLint  
* Prettier  
* TypeScript checks

# 5\. Arsitektur Aplikasi

# NusaHR menggunakan satu aplikasi Laravel. React dan TypeScript dirender melalui Inertia, bukan sebagai aplikasi API terpisah. Named routes mengarahkan request ke controller, Form Request menangani validasi dan otorisasi, policy melindungi resource, action khusus menjalankan workflow transaksional, dan Eloquent menangani persistence.

Workflow yang memerlukan konsistensi, misalnya saldo cuti, kehadiran, dan publikasi payroll dilindungi oleh transaction dan row locking. Notifikasi database diproses melalui queue, sedangkan scheduler menjalankan proses absensi, publikasi pengumuman terjadwal, dan pengecekan dokumen yang akan kedaluwarsa.

# 6\. Fitur Utama

Manajemen karyawan dan organisasi: profil karyawan, nomor karyawan otomatis, departemen, jabatan, lokasi, tipe kepegawaian, struktur manager, histori gaji, deaktivasi, dan dokumen privat.

* **Cuti:** perhitungan hari kerja, cuti penuh atau setengah hari, validasi saldo dan overlap, lampiran, reservasi saldo saat pending, approval/rejection/cancellation, notifikasi, serta audit trail.  
* **Kehadiran:** check-in/check-out, toleransi jadwal, perhitungan keterlambatan dan durasi kerja, koreksi oleh HR, visibilitas tim langsung, dan proses absensi yang memperhatikan weekend, hari libur, serta cuti yang disetujui.  
* **Payroll sederhana:** komponen gaji efektif per tanggal, perhitungan fixed atau percentage, deduction untuk absensi/cuti tidak dibayar, adjustment manual, draft deterministik, publikasi transaksional, ekspor CSV, serta payslip privat yang dapat dicetak.  
* **Komunikasi dan insight:** pengumuman draft/terjadwal dengan audience berbasis peran/departemen/lokasi, pelacakan pembacaan, notification center, metrik dashboard, laporan CSV, pengaturan, dan audit viewer dengan data sensitif yang dimasking.

# 7\. Peran dan Hak Akses

Sistem mendukung empat peran: Super Admin, HR Admin, Manager, dan Employee. 

* **Super Admin** mengelola role, akun Super Admin, pengaturan perusahaan, dan audit log.   
* **HR Admin** mengelola master data, lifecycle karyawan, cuti, kehadiran, payroll, pengumuman, serta laporan; namun tidak dapat memodifikasi atau menonaktifkan akun Super Admin.  
* **Manager** hanya melihat diri sendiri dan direct report, dapat meninjau cuti tim langsung, serta melihat kehadiran tim.   
* **Employee** mengakses profil sendiri, mengajukan cuti, melakukan absensi, menerima notifikasi, membaca pengumuman, mengunduh dokumen pribadi, dan melihat payslip yang sudah dipublikasikan. Hak akses selalu ditegakkan di backend oleh policy dan authorization, bukan hanya dengan menyembunyikan menu.

# 8\. Aturan Bisnis Penting

Nomor karyawan dan email bersifat unik. Master data yang tidak aktif tidak dapat dipilih. Sistem menolak manager diri sendiri maupun hubungan manager yang membentuk siklus.  
Hari kerja mengecualikan weekend dan hari libur. Pengajuan cuti ditolak bila overlap atau melebihi saldo. Saat masih pending, saldo direservasi; approval memindahkan saldo menjadi used, sedangkan rejection atau cancellation melepaskan reservasi.  
Setiap karyawan hanya dapat check-in sekali per hari. Check-out harus mengikuti check-in dan akan menghitung total durasi kerja. Payroll menyimpan snapshot data gaji agar perubahan data karyawan di masa depan tidak mengubah payslip historis; hanya payroll period yang published dapat dilihat employee.

# 9\. Keamanan dan Privasi

Autentikasi menggunakan Laravel Fortify dengan login, reset password, verifikasi email, two-factor authentication, passkey, throttling, registrasi publik yang dinonaktifkan, dan pemblokiran akun tidak aktif.  
Dokumen karyawan dan lampiran cuti disimpan di private disk dengan path acak, validasi tipe dan ukuran file, serta download melalui controller yang melakukan authorization. Data rekening terenkripsi dan field gaji/bank disembunyikan dari serialisasi. Audit metadata melakukan redaksi rekursif untuk password, token, rekening, gaji, dan data sensitif lainnya.

# 10\. Kualitas, Pengujian, dan Operasional

Pengujian menggunakan Pest dengan SQLite terisolasi, sementara CI memvalidasi migrasi dan seeding menggunakan PostgreSQL. Cakupan pengujian mencakup autentikasi, authorization berbasis peran, lifecycle karyawan, dokumen privat, alur cuti, kehadiran, payroll, pengumuman, laporan, pengaturan, notifikasi, dan masking audit.  
Proses quality gate mencakup test suite, Pint, pemeriksaan TypeScript, ESLint, Prettier, build Vite, serta audit dependency Composer dan npm. Untuk deployment diperlukan queue worker yang disupervisi, scheduler cron setiap menit, HTTPS, backup terenkripsi, monitoring failed jobs, serta penggunaan secret manager untuk APP\_KEY dan DATABASE\_URL.

# 11\. Batasan Saat Ini dan Pengembangan Lanjutan

Payroll pada NusaHR sengaja dibuat sebagai perhitungan gross sederhana untuk kebutuhan portfolio. Kepatuhan statutory Indonesia seperti PPh 21/BPJS dan integrasi transfer bank belum diimplementasikan. Geofencing lanjutan, perangkat biometrik, SSO/SCIM, impersonation, multi-step approval yang dapat dikonfigurasi, antivirus object storage, workforce forecasting, dan visual regression testing merupakan kandidat pengembangan berikutnya.

# 12\. Video Perkenalan Diri

\[Tambahkan tautan video perkenalan diri di sini\]

# 13\. Screenshots Aplikasi

\[Tambahkan screenshots dashboard, employee directory, leave approval, attendance, payroll review, payslip, serta versi mobile di sini\]

# 14\. Live Demo

\[Tambahkan tautan live demo dan kredensial demo yang aman di sini\]

# Penutup

NusaHR menunjukkan kemampuan pengembangan full-stack melalui implementasi aplikasi Laravel dan React yang berfokus pada keamanan, role-based access control, workflow bisnis, data consistency, testing, dan kesiapan operasional.  
