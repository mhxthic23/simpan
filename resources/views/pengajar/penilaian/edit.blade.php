@extends('layouts.app')

@section('title', 'Penilaian · '.$tugas->judul)

@section('content')
<a class="text-decoration-none" href="{{ route('pengajar.tugas.pengumpulan.show', [$tugas, $submission]) }}">← Kembali ke pengumpulan</a><h1 class="h2 mt-2 mb-1">Penilaian Tugas</h1><p class="text-secondary mb-4">{{ $tugas->judul }} · {{ $submission->siswa->name }} · Attempt {{ $submission->attempt_no }}</p>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="card border-0 shadow-sm mb-4"><div class="card-body p-4"><p class="mb-2"><strong>Berkas:</strong> {{ $submission->original_filename }}</p><p class="mb-0"><strong>Dikumpulkan:</strong> {{ $submission->submitted_at?->format('d M Y H:i:s') }}</p><a class="btn btn-sm btn-outline-primary mt-3" href="{{ route('pengajar.pengumpulan.download', $submission) }}">Unduh berkas</a></div></div>
@if($criteria->isEmpty())<div class="alert alert-warning">Tugas ini belum memiliki rubrik. <a href="{{ route('pengajar.tugas.rubrik.index', $tugas) }}">Buat rubrik terlebih dahulu.</a></div>
@elseif($totalBobotCents !== 10000)<div class="alert alert-warning">Total bobot saat ini {{ number_format($totalBobotCents / 100, 2) }}%. Rubrik harus tepat 100% sebelum penilaian dapat disimpan. <a href="{{ route('pengajar.tugas.rubrik.index', $tugas) }}">Perbaiki rubrik.</a></div>
@else
@php($assessment = $submission->penilaian)
@php($existingDetails = $assessment?->detail?->keyBy('rubrik_id') ?? collect())
<form method="POST" action="{{ route('pengajar.penilaian.save', [$tugas, $submission]) }}" class="card border-0 shadow-sm"><div class="card-body p-4">@csrf
<div class="table-responsive"><table class="table align-middle"><thead class="table-light"><tr><th>Kriteria</th><th>Bobot</th><th>Indikator / skor maksimum</th><th style="min-width: 160px">Skor</th><th>Komentar kriteria</th></tr></thead><tbody>@foreach($criteria as $criterion)@php($detail = $existingDetails->get($criterion->id))<tr><td><strong>{{ $criterion->nama }}</strong></td><td>{{ number_format((float)$criterion->bobot, 2) }}%</td><td>{{ $criterion->deskripsi ?: '—' }}<div class="small text-secondary">Maksimum {{ number_format((float)$criterion->skor_maksimum, 2) }}</div></td><td><input name="scores[{{ $criterion->id }}]" type="number" min="0" max="{{ $criterion->skor_maksimum }}" step="0.01" value="{{ old('scores.'.$criterion->id, $detail?->skor) }}" class="form-control" required></td><td><textarea name="detail_feedback[{{ $criterion->id }}]" rows="2" class="form-control">{{ old('detail_feedback.'.$criterion->id, $detail?->feedback) }}</textarea></td></tr>@endforeach</tbody></table></div>
<div class="mb-3"><label for="feedback" class="form-label">Umpan balik untuk siswa</label><textarea id="feedback" name="feedback" rows="4" class="form-control">{{ old('feedback', $assessment?->feedback) }}</textarea></div>
@if($assessment?->published_at)<div class="mb-3"><label for="alasan_perubahan" class="form-label">Alasan perubahan nilai</label><textarea id="alasan_perubahan" name="alasan_perubahan" rows="3" class="form-control" required>{{ old('alasan_perubahan') }}</textarea></div><input type="hidden" name="status" value="published"><button class="btn btn-primary" type="submit">Simpan perubahan nilai</button>
@else<div class="mb-3"><label for="status" class="form-label">Status penyimpanan</label><select id="status" name="status" class="form-select"><option value="draft" @selected(old('status', $assessment?->status ?? 'draft') === 'draft')>Simpan sebagai draft</option><option value="published" @selected(old('status', $assessment?->status) === 'published')>Publikasikan nilai kepada siswa</option></select></div><button class="btn btn-primary" type="submit">Simpan penilaian</button>@endif
</div></form>
@endif
@if($submission->penilaian?->riwayat?->isNotEmpty())
<div class="card border-0 shadow-sm mt-4"><div class="card-header bg-white"><h2 class="h5 mb-0">Riwayat perubahan nilai</h2></div><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Waktu</th><th>Pengubah</th><th>Sebelum</th><th>Sesudah</th><th>Alasan</th></tr></thead><tbody>
@foreach($submission->penilaian->riwayat as $entry)<tr><td>{{ $entry->created_at?->format('d M Y H:i') }}</td><td>{{ $entry->pengubah->name }}</td><td>{{ $entry->nilai_sebelumnya ?? '—' }}</td><td>{{ $entry->nilai_baru }}</td><td>{{ $entry->alasan_perubahan ?: '—' }}</td></tr>@endforeach
</tbody></table></div></div>
@endif
@endsection
