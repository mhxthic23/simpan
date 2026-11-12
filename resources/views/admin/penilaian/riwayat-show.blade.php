@extends('layouts.app')

@section('title', 'Detail Audit Nilai · SIMPAN')

@section('content')
<a class="text-decoration-none" href="{{ route('admin.penilaian.riwayat.index') }}">← Kembali ke audit</a><h1 class="h2 mt-2 mb-1">Riwayat nilai</h1><p class="text-secondary mb-4">{{ $penilaian->pengumpulanTugas->siswa->name }} · {{ $penilaian->pengumpulanTugas->tugas->judul }} · Attempt {{ $penilaian->pengumpulanTugas->attempt_no }}</p>
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Waktu</th><th>Pengubah</th><th>Nilai sebelum</th><th>Nilai sesudah</th><th>Alasan</th><th>Snapshot</th></tr></thead><tbody>@forelse($penilaian->riwayat as $entry)<tr><td>{{ $entry->created_at?->format('d M Y H:i:s') }}</td><td>{{ $entry->pengubah->name }}</td><td>{{ $entry->nilai_sebelumnya ?? '—' }}</td><td>{{ $entry->nilai_baru }}</td><td>{{ $entry->alasan_perubahan ?: '—' }}</td><td><details><summary>Lihat rincian</summary><pre class="small text-wrap mt-2 mb-0">{{ json_encode($entry->snapshot_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre></details></td></tr>@empty<tr><td colspan="6" class="text-center text-secondary py-5">Belum ada riwayat untuk penilaian ini.</td></tr>@endforelse</tbody></table></div></div>
@endsection
