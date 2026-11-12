@extends('layouts.app')

@section('title', 'Dashboard Admin · SIMPAN')

@section('content')
<div class="mb-4">
    <span class="badge text-bg-dark mb-2">Area admin</span>
    <h1 class="h2">Selamat datang, {{ $user->name }}</h1>
    <p class="text-secondary mb-0">Anda masuk sebagai {{ $user->role }}.</p>
</div>
<div class="card border-0 shadow-sm"><div class="card-body p-4">
    <h2 class="h5">Administrasi SIMPAN</h2>
    <p class="text-secondary">Pilih modul administrasi yang ingin dikelola.</p>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-primary" href="{{ route('admin.users.index') }}">Pengguna</a>
        <a class="btn btn-outline-primary" href="{{ route('admin.fakultas.index') }}">Fakultas</a>
        <a class="btn btn-outline-primary" href="{{ route('admin.jurusan.index') }}">Jurusan</a>
        <a class="btn btn-primary" href="{{ route('admin.kelas.index') }}">Kelas</a>
    </div>
</div></div>
@endsection
