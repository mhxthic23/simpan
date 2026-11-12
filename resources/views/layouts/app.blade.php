<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SIMPAN')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="{{ url('/') }}">SIMPAN</a>
            <div class="d-flex flex-wrap align-items-center gap-3">
                @auth
                    @if (auth()->user()->role === 'admin')
                        <a class="link-light text-decoration-none" href="{{ route('admin.users.index') }}">Pengguna</a>
                        <a class="link-light text-decoration-none" href="{{ route('admin.fakultas.index') }}">Fakultas</a>
                        <a class="link-light text-decoration-none" href="{{ route('admin.jurusan.index') }}">Jurusan</a>
                        <a class="link-light text-decoration-none" href="{{ route('admin.kelas.index') }}">Kelas</a>
                        <a class="link-light text-decoration-none" href="{{ route('admin.penilaian.riwayat.index') }}">Audit nilai</a>
                    @elseif (auth()->user()->role === 'pengajar')
                        <a class="link-light text-decoration-none" href="{{ route('pengajar.tugas.index') }}">Tugas</a>
                        <a class="link-light text-decoration-none" href="{{ route('pengajar.penilaian.index') }}">Penilaian</a>
                        <a class="link-light text-decoration-none" href="{{ route('pengajar.disputasi.index') }}">Keberatan</a>
                        <a class="link-light text-decoration-none" href="{{ route('pengajar.portfolio.students.index') }}">Portfolio siswa</a>
                    @elseif (auth()->user()->role === 'siswa')
                        <a class="link-light text-decoration-none" href="{{ route('siswa.tugas.index') }}">Tugas</a>
                        <a class="link-light text-decoration-none" href="{{ route('siswa.nilai.index') }}">Nilai</a>
                        <a class="link-light text-decoration-none" href="{{ route('siswa.disputasi.index') }}">Keberatan</a>
                        <a class="link-light text-decoration-none" href="{{ route('siswa.portfolio.index') }}">Portfolio saya</a>
                    @endif
                    <a class="link-light text-decoration-none" href="{{ route('notifikasi.index') }}">Notifikasi</a>
                    <a class="link-light text-decoration-none" href="{{ route('reminder.index') }}">Pengingat</a>
                    <a class="link-light text-decoration-none" href="{{ route('blockchain.index') }}">Blockchain</a>
                    <a class="link-light text-decoration-none" href="{{ route('marketplace.index') }}">Marketplace</a>
                    <a class="link-light text-decoration-none" href="{{ route('reputation.index') }}">Reputasi</a>
                    @if (auth()->user()->role === 'pengajar' || auth()->user()->role === 'siswa')
                        <a class="link-light text-decoration-none" href="{{ route(auth()->user()->role.'.voting.index') }}">Voting</a>
                        <a class="link-light text-decoration-none" href="{{ route(auth()->user()->role.'.diagnosis.index') }}">Diagnosis</a>
                    @endif
                    <span class="navbar-text text-white">{{ auth()->user()->name }} · {{ ucfirst(auth()->user()->role ?? 'tanpa role') }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-outline-light btn-sm" type="submit">Keluar</button>
                    </form>
                @else
                    <a class="btn btn-outline-light btn-sm" href="{{ route('login') }}">Masuk</a>
                    <a class="btn btn-light btn-sm text-primary" href="{{ route('register') }}">Daftar</a>
                @endauth
            </div>
        </div>
    </nav>
    <main class="container py-5">
        @yield('content')
    </main>
</body>
</html>
