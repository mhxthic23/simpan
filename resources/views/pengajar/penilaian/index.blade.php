@extends('layouts.app')

@section('title', 'Antrean Penilaian · SIMPAN')

@section('content')
<h1 class="h2 mb-1">Antrean Penilaian</h1><p class="text-secondary mb-4">Pengumpulan dari kelas yang Anda ajar dan belum memiliki nilai terpublikasi.</p>
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Siswa</th><th>Tugas / Kelas</th><th>Attempt</th><th>Dikumpulkan</th><th>Status nilai</th><th></th></tr></thead><tbody>@forelse($pengumpulan as $item)<tr><td>{{ $item->siswa->name }}</td><td>{{ $item->tugas->judul }}<div class="small text-secondary">{{ $item->tugas->kelas->nama }}</div></td><td>{{ $item->attempt_no }}</td><td>{{ $item->submitted_at?->format('d M Y H:i') }}</td><td>{{ $item->penilaian?->status === 'draft' ? 'Draft' : 'Belum dinilai' }}</td><td class="text-end"><a class="btn btn-sm btn-primary" href="{{ route('pengajar.penilaian.edit', [$item->tugas, $item]) }}">Buka penilaian</a></td></tr>@empty<tr><td colspan="6" class="text-center text-secondary py-4">Semua pengumpulan sudah memiliki nilai terpublikasi.</td></tr>@endforelse</tbody></table></div></div><div class="mt-3">{{ $pengumpulan->links('pagination::bootstrap-5') }}</div>
@endsection
