@extends('layouts.app')

@section('title', 'Buat Tugas · SIMPAN')

@section('content')
<div class="row justify-content-center"><div class="col-lg-8"><a href="{{ route('pengajar.tugas.index') }}" class="text-decoration-none">← Kembali ke tugas</a><h1 class="h2 mt-2 mb-4">Buat Tugas</h1><div class="card border-0 shadow-sm"><div class="card-body p-4">@include('pengajar.tugas._form', ['action' => route('pengajar.tugas.store'), 'method' => 'POST', 'buttonLabel' => 'Simpan tugas'])</div></div></div></div>
@endsection
