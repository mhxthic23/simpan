@extends('layouts.app')

@section('title', 'Belum Mengumpulkan · '.$tugas->judul)

@section('content')
<a href="{{ route('pengajar.tugas.show', $tugas) }}" class="text-decoration-none">← Kembali ke detail tugas</a><h1 class="h2 mt-2 mb-1">Belum Mengumpulkan</h1><p class="text-secondary mb-4">{{ $tugas->judul }}</p>
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Nama siswa</th><th>Email</th></tr></thead><tbody>@forelse($siswaBelumMengumpulkan as $siswa)<tr><td>{{ $siswa->name }}</td><td>{{ $siswa->email }}</td></tr>@empty<tr><td colspan="2" class="text-center text-secondary py-4">Semua siswa di kelas ini sudah mengumpulkan.</td></tr>@endforelse</tbody></table></div></div><div class="mt-3">{{ $siswaBelumMengumpulkan->links('pagination::bootstrap-5') }}</div>
@endsection
