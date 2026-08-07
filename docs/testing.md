# Testing

Feature tests memakai SQLite terisolasi dari Neon. Jalankan `php artisan test --compact`; PostgreSQL integration test harus memakai database test khusus. Pemeriksaan wajib: Pint, Larastan, TypeScript, ESLint, Prettier, dan Vite production build. Jangan pernah menunjuk `phpunit.xml` ke DATABASE_URL produksi/shared.
