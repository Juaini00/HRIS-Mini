# Desain database

Nilai uang memakai `decimal(15,2)`. Foreign key menggunakan restrict untuk master data bersejarah dan cascade hanya untuk aggregate-owned data. Unique constraints melindungi nomor karyawan, satu presensi per hari, satu saldo per tahun/jenis, periode payroll, dan satu record payroll per karyawan.

```mermaid
erDiagram
 USERS ||--o| EMPLOYEES : owns
 DEPARTMENTS ||--o{ POSITIONS : has
 DEPARTMENTS ||--o{ EMPLOYEES : groups
 EMPLOYEES ||--o{ EMPLOYEES : manages
 EMPLOYEES ||--o{ LEAVE_REQUESTS : submits
 LEAVE_TYPES ||--o{ LEAVE_REQUESTS : classifies
 EMPLOYEES ||--o{ ATTENDANCES : records
 PAYROLL_PERIODS ||--o{ PAYROLL_RECORDS : contains
 EMPLOYEES ||--o{ PAYROLL_RECORDS : receives
 USERS ||--o{ ANNOUNCEMENTS : authors
 EMPLOYEES ||--o{ EMPLOYEE_DOCUMENTS : owns
```
