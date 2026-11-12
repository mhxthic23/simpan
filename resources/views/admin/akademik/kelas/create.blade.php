@extends('layouts.app')

@section('title', 'Tambah Kelas · SIMPAN')

@section('content')
@include('admin.akademik._nav')
<div class="row justify-content-center"><div class="col-lg-8"><a href="{{ route('admin.kelas.index') }}" class="text-decoration-none">← Kembali ke kelas</a><h1 class="h2 mt-2 mb-4">Tambah Kelas</h1>
<div class="card border-0 shadow-sm"><div class="card-body p-4">@include('admin.akademik.kelas._form', ['action' => route('admin.kelas.store'), 'method' => 'POST', 'buttonLabel' => 'Simpan kelas'])</div></div>
</div></div>
@endsection
