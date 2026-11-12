@extends('layouts.app')

@section('title', 'Audit Riwayat Nilai · SIMPAN')

@section('content')
<h1 class="h2 mb-1">Audit riwayat nilai</h1><p class="text-secondary mb-4">Perubahan nilai dan status publikasi.</p>
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Waktu</th><th>Siswa</th><th>Tugas</th><th>Pengubah</th><th>Sebelum</th><th>Sesudah</th><th>Alasan</th><th></th></tr></thead><tbody>@forelse($riwayat as $entry)@php($assessment = $entry->penilaian)<tr><td>{{ $entry->created_at?->format('d M Y H:i') }}</td><td>{{ $assessment?->pengumpulanTugas?->siswa?->name ?? 'Data tidak tersedia' }}</td><td>{{ $assessment?->pengumpulanTugas?->tugas?->judul ?? '—' }}</td><td>{{ $entry->pengubah?->name ?? '—' }}</td><td>{{ $entry->nilai_sebelumnya ?? '—' }}</td><td>{{ $entry->nilai_baru }}</td><td>{{ $entry->alasan_perubahan ?: '—' }}</td><td>@if($assessment)<a class="btn btn-sm btn-outline-primary" href="{{ route('admin.penilaian.riwayat.show', $assessment) }}">Detail</a>@endif</td></tr>@empty<tr><td colspan="8" class="text-center text-secondary py-5">Belum ada riwayat penilaian.</td></tr>@endforelse</tbody></table></div></div><div class="mt-3">{{ $riwayat->links() }}</div>
@endsection
