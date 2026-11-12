@extends('layouts.app')

@section('title', 'Keberatan Saya · SIMPAN')

@section('content')
<h1 class="h2 mb-1">Keberatan Nilai Saya</h1><p class="text-secondary mb-4">Status dan tanggapan untuk keberatan yang Anda ajukan.</p>
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Tugas</th><th>Nilai</th><th>Status</th><th>Diajukan</th><th></th></tr></thead><tbody>@forelse($disputasi as $item)<tr><td>{{ $item->penilaian->pengumpulanTugas->tugas->judul }}</td><td>{{ $item->penilaian->nilai_total }}</td><td><span class="badge text-bg-info">{{ ucfirst($item->status) }}</span></td><td>{{ $item->submitted_at->format('d M Y H:i') }}</td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('siswa.nilai.show', $item->penilaian) }}">Detail</a></td></tr>@empty<tr><td colspan="5" class="text-center text-secondary py-4">Belum ada keberatan yang diajukan.</td></tr>@endforelse</tbody></table></div></div><div class="mt-3">{{ $disputasi->links('pagination::bootstrap-5') }}</div>
@endsection
