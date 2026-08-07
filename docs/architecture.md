# Arsitektur

Browser menjalankan React/TypeScript melalui Inertia, sedangkan Laravel menangani routing, validasi, otorisasi, transaction, queue, scheduler, dan persistence PostgreSQL. Controller mendelegasikan workflow stateful ke `app/Actions`; Eloquent menjadi boundary data tanpa generic repository.

Fortify menangani login, reset password, verifikasi email, 2FA, dan passkey. Registrasi publik dimatikan. Empat role disimpan sebagai enum dan seluruh keputusan sensitif diperiksa di backend. Cuti dan payroll menggunakan transaction serta row lock. Scheduler memproses ketidakhadiran idempoten dan publikasi terjadwal. Dokumen karyawan harus dilayani melalui controller terotorisasi dari disk privat; path storage tidak boleh dibagikan langsung. Audit schema tidak menyimpan password atau secret.
