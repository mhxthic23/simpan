@extends('layouts.app')

@section('title', 'Daftar Tugas · SIMPAN')

@section('content')
<div class="mb-4"><h1 class="h2 mb-1">Daftar Tugas</h1><p class="text-secondary mb-0">Tugas terbit dari kelas yang Anda ikuti.</p></div>
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<form method="GET" class="card card-body border-0 shadow-sm mb-4"><div class="row g-2"><div class="col-md-10"><label for="q" class="form-label">Cari tugas</label><input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control"></div><div class="col-md-2 d-flex align-items-end gap-2"><button class="btn btn-outline-primary" type="submit">Cari</button><a class="btn btn-outline-secondary" href="{{ route('siswa.tugas.index') }}">Reset</a></div></div></form>
<div class="row g-3">@forelse($tugas as $item)@php($latestSubmission = $item->pengumpulanTugas->first())<div class="col-md-6 col-xl-4"><div class="card border-0 shadow-sm h-100"><div class="card-body d-flex flex-column"><div class="d-flex justify-content-between gap-2"><h2 class="h5">{{ $item->judul }}</h2>@if($latestSubmission)<span class="badge text-bg-success align-self-start">Terkumpul</span>@else<span class="badge text-bg-warning align-self-start">Belum dikumpulkan</span>@endif</div><p class="text-secondary small mb-2">{{ $item->kelas->nama }} · {{ $item->pengajar->name }}</p><p class="small mb-3">Tenggat: <strong>{{ $item->deadline_at->format('d M Y H:i') }}</strong></p><a class="btn btn-outline-primary mt-auto" href="{{ route('siswa.tugas.show', $item) }}">Lihat tugas</a></div></div></div>@empty<div class="col-12"><div class="card border-0"><div class="card-body text-center text-secondary py-5">Belum ada tugas terbit dari kelas yang Anda ikuti.</div></div></div>@endforelse</div><div class="mt-3">{{ $tugas->links('pagination::bootstrap-5') }}</div>
@endsection
