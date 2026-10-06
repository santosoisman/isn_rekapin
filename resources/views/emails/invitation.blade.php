<p>Halo,</p>
<p><strong>{{ $invitation->inviter->name }}</strong> mengajak kamu untuk kolaborasi mencatat keuangan bersama di buku "<strong>{{ $invitation->book->name }}</strong>".</p>
<p>
    <a href="{{ $url }}" style="background:#4f46e5;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;">
        Terima Undangan
    </a>
</p>
<p>Kalau kamu belum punya akun, cukup login/daftar pakai Google dengan email ini ({{ $invitation->email }}), nanti otomatis tergabung.</p>
