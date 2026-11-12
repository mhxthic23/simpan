@extends('layouts.app')

@section('title', 'Nilai Saya · SIMPAN')

@section('content')
<h1 class="h2 mb-1">Nilai Saya</h1><p class="text-secondary mb-4">Hanya nilai yang sudah dipublikasikan pengajar yang ditampilkan.</p>
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Tugas</th><th>Attempt</th><th>Nilai</th><th>Dipublikasikan</th><th>Keberatan</th><th></th></tr></thead><tbody>@forelse($penilaian as $nilai)<tr><td>{{ $nilai->pengumpulanTugas->tugas->judul }}</td><td>{{ $nilai->pengumpulanTugas->attempt_no }}</td><td><strong>{{ $nilai->nilai_total }}</strong></td><td>{{ $nilai->published_at?->format('d M Y H:i') }}</td><td>{{ $nilai->disputasi ? ucfirst($nilai->disputasi->status) : 'Belum diajukan' }}</td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('siswa.nilai.show', $nilai) }}">Detail</a></td></tr>@empty<tr><td colspan="6" class="text-center text-secondary py-4">Belum ada nilai yang dipublikasikan.</td></tr>@endforelse</tbody></table></div></div><div class="mt-3">{{ $penilaian->links('pagination::bootstrap-5') }}</div>
@endsection
