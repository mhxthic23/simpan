@extends('layouts.app')

@section('title', 'Buat Pengguna · SIMPAN')

@section('content')
<div class="row justify-content-center"><div class="col-lg-8">
    <div class="mb-4"><a href="{{ route('admin.users.index') }}" class="text-decoration-none">← Kembali ke pengguna</a><h1 class="h2 mt-2">Buat akun pengguna</h1></div>
    <div class="card border-0 shadow-sm"><div class="card-body p-4">
        @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf
            <div class="mb-3"><label for="name" class="form-label">Nama</label><input id="name" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="mb-3"><label for="email" class="form-label">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="mb-3"><label for="role" class="form-label">Role</label><select id="role" name="role" class="form-select @error('role') is-invalid @enderror" required><option value="">Pilih role</option>@foreach(['siswa' => 'Siswa', 'pengajar' => 'Pengajar', 'admin' => 'Admin'] as $value => $label)<option value="{{ $value }}" @selected(old('role', 'pengajar') === $value)>{{ $label }}</option>@endforeach</select>@error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="mb-3"><label for="password" class="form-label">Kata sandi awal</label><input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" required><div class="form-text">Minimal 8 karakter. Kata sandi tidak akan ditampilkan kembali.</div>@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="mb-4"><label for="password_confirmation" class="form-label">Konfirmasi kata sandi</label><input id="password_confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password" required></div>
            <button class="btn btn-primary" type="submit">Simpan akun</button>
        </form>
    </div></div>
</div></div>
@endsection
