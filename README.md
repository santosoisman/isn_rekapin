# Scaffold: Buku Keuangan (Laravel 12 + PHP 8.2)

Fitur yang sudah lengkap di scaffold ini:
- Landing page + login Google
- Multi-buku per user, dengan satu "buku aktif" di session
- Kolaborasi lewat undangan email → begitu login dengan email yang diundang, otomatis jadi member
- Pemasukan & pengeluaran (kategori: Dasar, Tanpa Rencana, Jajan)
- **Edit & hapus transaksi** (halaman `/transactions`), dengan otorisasi: pembuat transaksi
  atau owner buku yang boleh edit/hapus (lihat `app/Policies/IncomePolicy.php` & `ExpensePolicy.php`)
- **Kelola buku** (`/books/manage`): rename buku, lihat & keluarkan anggota (khusus owner),
  batalkan undangan pending
- History dalam bentuk kalender (klik tanggal → detail transaksi hari itu via AJAX)
- **Tailwind + Alpine dibundel lewat Vite** (bukan CDN lagi) — siap untuk produksi

Karena environment saya tidak punya akses ke Packagist/NPM registry untuk project PHP,
file-file di sini HARUS ditempelkan ke project Laravel 12 yang kamu buat sendiri di lokal.

## 1. Buat project Laravel 12 baru
```bash
composer create-project laravel/laravel keuangan-app "^12.0"
cd keuangan-app
composer require laravel/socialite
```

## 2. Salin semua file dari scaffold ini ke project barumu
Timpa/tambahkan folder & file berikut persis sesuai strukturnya:
```
app/Http/Controllers/*.php            (termasuk Controller.php dasar — sudah pakai AuthorizesRequests)
app/Http/Controllers/Auth/GoogleController.php
app/Http/Middleware/EnsureCurrentBook.php
app/Mail/InvitationMail.php
app/Models/*.php
app/Policies/*.php                    (auto-discovered oleh Laravel, tidak perlu didaftarkan manual)
database/migrations/*.php
resources/css/app.css
resources/js/app.js
resources/views/*                     (landing, dashboard, layouts/, books/, history/, transactions/, emails/)
routes/web.php                        (replace isi bawaan)
tailwind.config.js
postcss.config.js
vite.config.js                        (replace bawaan)
package.json                          (replace bawaan, atau merge devDependencies-nya)
```

## 3. Daftarkan middleware
Buka `bootstrap/app.php`, tambahkan di dalam `->withMiddleware(...)`:
```php
$middleware->alias([
    'current.book' => \App\Http\Middleware\EnsureCurrentBook::class,
]);
```
(lihat detail di `bootstrap-middleware-snippet.txt`)

## 4. Tambahkan config Google
Copy isi `config/services-snippet.php` ke dalam `config/services.php` (di dalam array return).

## 5. Install dependency & build asset
```bash
composer install
npm install
npm run build     # atau `npm run dev` saat development
```

## 6. Setup Google OAuth & Mail
- Buat OAuth Client di https://console.cloud.google.com/apis/credentials
- Authorized redirect URI: `http://localhost:8000/auth/google/callback`
- Isi `.env`:
```
GOOGLE_CLIENT_ID=xxxxx
GOOGLE_CLIENT_SECRET=xxxxx
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback

MAIL_MAILER=smtp
MAIL_HOST=xxxx
MAIL_PORT=587
MAIL_USERNAME=xxxx
MAIL_PASSWORD=xxxx
MAIL_FROM_ADDRESS=noreply@bukukeuangan.test
```

## 7. Migrate & jalankan
```bash
php artisan migrate
php artisan serve
npm run dev   # di terminal terpisah, kalau mau hot-reload asset saat development
```

## Ringkasan alur
1. `/` → "Masuk dengan Google" → `GoogleController@redirect` → `@callback`.
   User baru otomatis dibuat; undangan pending dengan email sama langsung di-attach.
2. Kalau user belum punya buku, middleware `current.book` redirect ke `/books/create`.
3. Dashboard: 3 tab (Alpine) — tambah pemasukan, tambah pengeluaran (pilih kategori), undang kolaborator.
4. `/transactions` — daftar penuh, tiap baris ada tombol Edit (inline form) & Hapus, dibatasi
   `@can('update', $tx)` sesuai policy (pemilik transaksi atau owner buku).
5. `/books/manage` — owner bisa rename buku, keluarkan member, batalkan undangan pending.
6. `/history` — kalender bulanan, klik tanggal → fetch `/history/{date}` (JSON) → tampil tanpa reload.

## Yang masih bisa dikembangkan lebih lanjut
- Queue untuk email undangan (`ShouldQueue` di `InvitationMail`) supaya tidak blocking request.
- Notifikasi in-app / email saat undangan diterima.
- Export laporan (PDF/Excel) per bulan.
- Rate limit endpoint undangan supaya tidak disalahgunakan untuk spam email.
