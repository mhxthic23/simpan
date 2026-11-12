@extends('layouts.app')

@section('title', 'Tugas Saya · SIMPAN')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div><h1 class="h2 mb-1">Tugas dari Kelas Saya</h1><p class="text-secondary mb-0">Kelola tugas yang ditujukan kepada siswa di kelas yang Anda ajar.</p></div><a class="btn btn-primary" href="{{ route('pengajar.tugas.create') }}">Buat tugas</a></div>
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
<form method="GET" class="card card-body border-0 shadow-sm mb-4"><div class="row g-3 align-items-end">
    <div class="col-md-6"><label for="q" class="form-label">Cari judul tugas</label><input id="q" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}"></div>
    <div class="col-md-4"><label for="kelas_id" class="form-label">Kelas</label><select id="kelas_id" name="kelas_id" class="form-select"><option value="">Semua kelas saya</option>@foreach($kelasList as $kelas)<option value="{{ $kelas->id }}" @selected((string)($filters['kelas_id'] ?? '') === (string)$kelas->id)>{{ $kelas->nama }} — {{ $kelas->jurusan->nama }}</option>@endforeach</select></div>
    <div class="col-md-2 d-flex gap-2"><button class="btn btn-outline-primary" type="submit">Cari</button><a class="btn btn-outline-secondary" href="{{ route('pengajar.tugas.index') }}">Reset</a></div>
</div></form>
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Tugas</th><th>Kelas</th><th>Tenggat</th><th>Status</th><th>Pengumpulan</th><th class="text-end">Tindakan</th></tr></thead><tbody>
@forelse($tugas as $item)@php($targetCount = $item->kelas->siswa_count ?? 0)@php($submittedCount = $item->siswa_sudah_mengumpulkan_count)@php($missingCount = max(0, $targetCount - $submittedCount))<tr><td>{{ $item->judul }}</td><td>{{ $item->kelas->nama }}</td><td>{{ $item->deadline_at->format('d M Y H:i') }}</td><td>@if($item->status === 'published')<span class="badge text-bg-success">Terbit</span>@else<span class="badge text-bg-secondary">Draft</span>@endif</td><td>{{ $submittedCount }} mengumpulkan<br><span class="small text-secondary">{{ $missingCount }} belum · {{ $targetCount }} siswa target</span></td><td class="text-end"><div class="d-inline-flex gap-2"><a class="btn btn-sm btn-outline-secondary" href="{{ route('pengajar.tugas.show', $item) }}">Detail</a><a class="btn btn-sm btn-outline-primary" href="{{ route('pengajar.tugas.edit', $item) }}">Ubah</a></div></td></tr>@empty<tr><td colspan="6" class="text-center text-secondary py-4">Belum ada tugas untuk ditampilkan.</td></tr>@endforelse
</tbody></table></div></div><div class="mt-3">{{ $tugas->links('pagination::bootstrap-5') }}</div>
@endsection
