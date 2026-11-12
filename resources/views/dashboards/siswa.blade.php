@extends('layouts.app')

@section('title', 'Dashboard Siswa · SIMPAN')

@section('content')
<div class="mb-4">
    <span class="badge text-bg-primary mb-2">Area siswa</span>
    <h1 class="h2">Selamat datang, {{ $user->name }}</h1>
    <p class="text-secondary mb-0">Anda masuk sebagai {{ $user->role }}.</p>
</div>
<div class="card border-0 shadow-sm"><div class="card-body p-4">
    <h2 class="h5">Fitur akademik</h2>
    <p class="text-secondary">Lihat tugas, nilai yang telah dipublikasikan, dan status keberatan Anda.</p>
    <div class="d-flex flex-wrap gap-2"><a class="btn btn-outline-primary" href="{{ route('siswa.tugas.index') }}">Lihat tugas</a><a class="btn btn-primary" href="{{ route('siswa.nilai.index') }}">Lihat nilai</a><a class="btn btn-outline-secondary" href="{{ route('siswa.disputasi.index') }}">Status keberatan</a><a class="btn btn-outline-success" href="{{ route('siswa.portfolio.index') }}">Portfolio saya</a><a class="btn btn-outline-info" href="{{ route('siswa.diagnosis.index') }}">Diagnosis akademik</a></div>
</div></div>
<div class="card border-0 shadow-sm mt-4"><div class="card-header bg-white"><h2 class="h5 mb-0">Nilai terbaru</h2></div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Tugas</th><th>Nilai</th><th>Dipublikasikan</th><th></th></tr></thead><tbody>@forelse($nilaiTerbaru as $item)<tr><td>{{ $item->pengumpulanTugas->tugas->judul }}</td><td>{{ $item->nilai_total }}</td><td>{{ $item->published_at?->format('d M Y') }}</td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('siswa.nilai.show', $item) }}">Detail nilai</a></td></tr>@empty<tr><td colspan="4" class="text-center text-secondary py-4">Belum ada nilai yang dipublikasikan.</td></tr>@endforelse</tbody></table></div></div>
<div class="card border-0 shadow-sm mt-4"><div class="card-header bg-white"><h2 class="h5 mb-0">Status keberatan</h2></div><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Tugas</th><th>Status</th><th>Diajukan</th></tr></thead><tbody>@forelse($disputasiTerbaru as $item)<tr><td>{{ $item->penilaian->pengumpulanTugas->tugas->judul }}</td><td><span class="badge text-bg-info">{{ ucfirst($item->status) }}</span></td><td>{{ $item->submitted_at?->format('d M Y H:i') }}</td></tr>@empty<tr><td colspan="3" class="text-center text-secondary py-4">Belum ada keberatan yang diajukan.</td></tr>@endforelse</tbody></table></div></div>
@endsection
