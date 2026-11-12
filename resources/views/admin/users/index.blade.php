@extends('layouts.app')

@section('title', 'Manajemen Pengguna · SIMPAN')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h2 mb-1">Manajemen Pengguna</h1>
        <p class="text-secondary mb-0">Cari akun, kelola role, dan atur status akses.</p>
    </div>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Buat akun pengguna</a>
</div>

@if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
@if ($errors->any())
    <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<form method="GET" action="{{ route('admin.users.index') }}" class="card card-body border-0 shadow-sm mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-md-6">
            <label for="q" class="form-label">Cari nama atau email</label>
            <input id="q" name="q" type="search" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="Nama atau email">
        </div>
        <div class="col-md-4">
            <label for="role" class="form-label">Filter role/status</label>
            <select id="role" name="role" class="form-select">
                <option value="">Semua pengguna</option>
                <option value="siswa" @selected(($filters['role'] ?? '') === 'siswa')>Siswa</option>
                <option value="pengajar" @selected(($filters['role'] ?? '') === 'pengajar')>Pengajar</option>
                <option value="admin" @selected(($filters['role'] ?? '') === 'admin')>Admin</option>
                <option value="nonaktif" @selected(($filters['role'] ?? '') === 'nonaktif')>Nonaktif</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-outline-primary flex-grow-1" type="submit">Cari</button>
            <a class="btn btn-outline-secondary" href="{{ route('admin.users.index') }}" aria-label="Bersihkan filter">Reset</a>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Nama</th><th>Email</th><th>Role</th><th>Status</th><th class="text-end">Tindakan</th></tr></thead>
            <tbody>
                @forelse ($users as $managedUser)
                    @php($isSelf = auth()->user()->is($managedUser))
                    <tr>
                        <td>{{ $managedUser->name }} @if($isSelf)<span class="badge text-bg-secondary">Anda</span>@endif</td>
                        <td>{{ $managedUser->email }}</td>
                        <td>{{ $managedUser->role ? ucfirst($managedUser->role) : '—' }}</td>
                        <td>
                            @if ($managedUser->role)
                                <span class="badge text-bg-success">Aktif</span>
                            @else
                                <span class="badge text-bg-secondary">Nonaktif</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                                <a href="{{ route('admin.users.edit', $managedUser) }}" class="btn btn-sm btn-outline-primary">Ubah</a>
                                @if (! $isSelf && $managedUser->role)
                                    <form method="POST" action="{{ route('admin.users.status', $managedUser) }}" onsubmit="return confirm('Nonaktifkan akun {{ $managedUser->name }}?');">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="active" value="0">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Nonaktifkan</button>
                                    </form>
                                @elseif (! $isSelf)
                                    <form method="POST" action="{{ route('admin.users.status', $managedUser) }}" class="d-flex gap-2" onsubmit="return confirm('Aktifkan akun {{ $managedUser->name }}?');">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="active" value="1">
                                        <select name="role" class="form-select form-select-sm" aria-label="Role untuk aktivasi" required>
                                            <option value="">Pilih role</option><option value="siswa">Siswa</option><option value="pengajar">Pengajar</option><option value="admin">Admin</option>
                                        </select>
                                        <button class="btn btn-sm btn-outline-success" type="submit">Aktifkan</button>
                                    </form>
                                @endif
                            </div>
                            @if($isSelf)<span class="small text-secondary d-block mt-1">Status dan role akun sendiri dikunci.</span>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-secondary py-4">Tidak ada pengguna yang cocok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $users->links('pagination::bootstrap-5') }}</div>
@endsection
