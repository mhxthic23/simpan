@extends('layouts.app')

@section('title', 'Ubah Tugas · SIMPAN')

@section('content')
<div class="row justify-content-center"><div class="col-lg-8"><a href="{{ route('pengajar.tugas.show', $tugas) }}" class="text-decoration-none">← Kembali ke detail tugas</a><h1 class="h2 mt-2 mb-4">Ubah Tugas</h1><div class="card border-0 shadow-sm"><div class="card-body p-4">@include('pengajar.tugas._form', ['action' => route('pengajar.tugas.update', $tugas), 'method' => 'PUT', 'buttonLabel' => 'Simpan perubahan'])</div></div></div></div>
@endsection
