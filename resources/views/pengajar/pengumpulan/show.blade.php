@extends('layouts.app')

@section('title', 'Detail Pengumpulan · SIMPAN')

@section('content')
<a href="{{ route('pengajar.tugas.pengumpulan.index', $tugas) }}" class="text-decoration-none">← Kembali ke daftar pengumpulan</a><h1 class="h2 mt-2 mb-4">Detail Pengumpulan</h1>
<div class="card border-0 shadow-sm"><div class="card-body p-4"><dl class="row mb-0"><dt class="col-sm-3">Tugas</dt><dd class="col-sm-9">{{ $tugas->judul }}</dd><dt class="col-sm-3">Siswa</dt><dd class="col-sm-9">{{ $pengumpulanTugas->siswa->name }} ({{ $pengumpulanTugas->siswa->email }})</dd><dt class="col-sm-3">Attempt</dt><dd class="col-sm-9">{{ $pengumpulanTugas->attempt_no }}</dd><dt class="col-sm-3">Waktu pengumpulan</dt><dd class="col-sm-9">{{ $pengumpulanTugas->submitted_at?->format('d M Y H:i:s') }}</dd><dt class="col-sm-3">Status</dt><dd class="col-sm-9">{{ ucfirst($pengumpulanTugas->status) }}</dd><dt class="col-sm-3">Berkas</dt><dd class="col-sm-9">{{ $pengumpulanTugas->original_filename ?: 'Berkas' }} ({{ number_format(($pengumpulanTugas->file_size ?? 0) / 1048576, 2) }} MB)</dd></dl><div class="d-flex flex-wrap gap-2 mt-4"><a class="btn btn-outline-primary" href="{{ route('pengajar.pengumpulan.download', $pengumpulanTugas) }}">Unduh berkas</a><a class="btn btn-primary" href="{{ route('pengajar.penilaian.edit', [$tugas, $pengumpulanTugas]) }}">{{ $pengumpulanTugas->penilaian ? 'Ubah penilaian' : 'Beri penilaian' }}</a></div></div></div>
@endsection
