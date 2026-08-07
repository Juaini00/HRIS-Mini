# Business rules

- Nomor dan akun karyawan unik; master data nonaktif tidak boleh dipilih.
- Hari kerja mengecualikan akhir pekan dan hari libur. Pengajuan overlap atau melebihi saldo ditolak. Pending balance direservasi dan dipindah ke used hanya setelah approval.
- Check-in hanya sekali sehari; check-out memerlukan check-in dan menghitung durasi. Proses absen melewati weekend, holiday, dan approved leave.
- Payroll membuat snapshot gaji agar perubahan historis tidak mengubah payslip. Hanya periode published terlihat oleh employee.

```mermaid
flowchart LR
 A[Ajukan cuti] --> B[Validasi overlap dan saldo] --> C[Reserve pending]
 C --> D{Review}
 D -->|Approve| E[Pending ke used]
 D -->|Reject| F[Lepas pending]
```

```mermaid
flowchart LR
 A[Check-in] --> B[Hitung terlambat] --> C[Check-out] --> D[Hitung durasi]
```

```mermaid
flowchart LR
 A[Buat periode] --> B[Snapshot karyawan dan gaji] --> C[Review] --> D[Publish terkunci]
```
