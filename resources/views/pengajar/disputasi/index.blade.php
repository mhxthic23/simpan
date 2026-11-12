@extends('layouts.app')

@section('title', 'Disputasi Nilai · SIMPAN')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div><h1 class="h2 mb-1">Disputasi nilai</h1><p class="text-secondary mb-0">Keberatan dari siswa pada kelas yang Anda ajar.</p></div>
    <form method="GET" class="d-flex gap-2"><select name="status" class="form-select" aria-label="Filter status"><option value="">Semua status</option>@foreach(['pending' => 'Menunggu', 'reviewing' => 'Ditinjau', 'accepted' => 'Diterima', 'rejected' => 'Ditolak'] as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select><button class="btn btn-outline-primary">Filter</button></form>
</div>
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Siswa</th><th>Tugas</th><th>Nilai</th><th>Diajukan</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($disputasi as $item)<tr><td>{{ $item->siswa->name }}</td><td>{{ $item->penilaian->pengumpulanTugas->tugas->judul }}</td><td>{{ $item->penilaian->nilai_total }}</td><td>{{ $item->submitted_at?->format('d M Y H:i') }}</td><td><span class="badge text-bg-{{ in_array($item->status, ['pending','reviewing']) ? 'warning' : ($item->status === 'accepted' ? 'success' : 'secondary') }}">{{ ucfirst($item->status) }}</span></td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('pengajar.disputasi.show', $item) }}">Tinjau</a></td></tr>@empty<tr><td colspan="6" class="text-center text-secondary py-5">Belum ada disputasi untuk filter ini.</td></tr>@endforelse
</tbody></table></div></div><div class="mt-3">{{ $disputasi->links() }}</div>
@endsection
