@extends('layouts.app')

@section('title', 'Ubah Pengguna · SIMPAN')

@section('content')
<div class="row justify-content-center"><div class="col-lg-8">
    <div class="mb-4"><a href="{{ route('admin.users.index') }}" class="text-decoration-none">← Kembali ke pengguna</a><h1 class="h2 mt-2">Ubah data pengguna</h1><p class="text-secondary mb-0">Kata sandi saat ini tidak ditampilkan. Biarkan kosong jika tidak ingin menggantinya.</p></div>
    <div class="card border-0 shadow-sm"><div class="card-body p-4">
        @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf @method('PUT')
            <div class="mb-3"><label for="name" class="form-label">Nama</label><input id="name" name="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="mb-3"><label for="email" class="form-label">Email</label><input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror" required>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            @if (auth()->user()->is($user))
                <div class="alert alert-info">Role admin pada akun Anda dikunci untuk menjaga akses administrasi.</div>
            @else
                <div class="mb-3"><label for="role" class="form-label">Role dan status</label><select id="role" name="role" class="form-select @error('role') is-invalid @enderror" required><option value="siswa" @selected(old('role', $user->role) === 'siswa')>Siswa — aktif</option><option value="pengajar" @selected(old('role', $user->role) === 'pengajar')>Pengajar — aktif</option><option value="admin" @selected(old('role', $user->role) === 'admin')>Admin — aktif</option><option value="nonaktif" @selected(old('role', $user->role ?? 'nonaktif') === 'nonaktif')>Nonaktif</option></select>@error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            @endif
            <div class="mb-3"><label for="password" class="form-label">Kata sandi baru <span class="text-secondary">(opsional)</span></label><input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password">@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="mb-4"><label for="password_confirmation" class="form-label">Konfirmasi kata sandi baru</label><input id="password_confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password"></div>
            <button class="btn btn-primary" type="submit">Simpan perubahan</button>
        </form>
    </div></div>
</div></div>
@endsection
