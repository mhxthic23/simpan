@extends('layouts.app')

@section('title', 'Dashboard Pengajar · SIMPAN')

@section('content')
<div class="mb-4">
    <span class="badge text-bg-success mb-2">Area pengajar</span>
    <h1 class="h2">Selamat datang, {{ $user->name }}</h1>
    <p class="text-secondary mb-0">Anda masuk sebagai {{ $user->role }}.</p>
</div>
<div class="card border-0 shadow-sm"><div class="card-body p-4">
    <h2 class="h5">Ruang pengajar</h2>
    <p class="text-secondary">Kelola tugas, nilai, dan keberatan pada kelas yang Anda ajar.</p>
    <div class="d-flex flex-wrap gap-2"><a class="btn btn-outline-primary" href="{{ route('pengajar.tugas.index') }}">Kelola tugas</a><a class="btn btn-outline-primary" href="{{ route('pengajar.penilaian.index') }}">Antrean penilaian</a><a class="btn btn-primary" href="{{ route('pengajar.disputasi.index') }}">Keberatan</a><a class="btn btn-outline-success" href="{{ route('pengajar.portfolio.students.index') }}">Portfolio siswa</a><a class="btn btn-outline-info" href="{{ route('pengajar.diagnosis.index') }}">Diagnosis akademik</a></div>
</div></div>
<div class="row g-3 mt-1"><div class="col-md-6"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-secondary small">Menunggu publikasi nilai</div><div class="fs-2 fw-semibold">{{ $menungguPenilaian }}</div><a href="{{ route('pengajar.penilaian.index') }}">Buka antrean penilaian</a></div></div></div><div class="col-md-6"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-secondary small">Keberatan perlu ditinjau</div><div class="fs-2 fw-semibold">{{ $disputasiCount }}</div><a href="{{ route('pengajar.disputasi.index', ['status' => 'pending']) }}">Tinjau keberatan</a></div></div></div></div>
<div class="card border-0 shadow-sm mt-4"><div class="card-header bg-white"><h2 class="h5 mb-0">Pengumpulan menunggu penilaian</h2></div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Siswa</th><th>Tugas</th><th>Waktu</th><th></th></tr></thead><tbody>@forelse($pengumpulanTerbaru as $item)<tr><td>{{ $item->siswa->name }}</td><td>{{ $item->tugas->judul }}</td><td>{{ $item->submitted_at?->format('d M Y H:i') }}</td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('pengajar.penilaian.edit', [$item->tugas, $item]) }}">Nilai</a></td></tr>@empty<tr><td colspan="4" class="text-center text-secondary py-4">Tidak ada pengumpulan yang menunggu publikasi nilai.</td></tr>@endforelse</tbody></table></div></div>
@endsection
