@extends('layouts.app')

@section('title', 'Detail Nilai · SIMPAN')

@section('content')
<a class="text-decoration-none" href="{{ route('siswa.nilai.index') }}">← Kembali ke nilai</a>
<h1 class="h2 mt-2 mb-1">{{ $penilaian->pengumpulanTugas->tugas->judul }}</h1>
<p class="text-secondary mb-4">Attempt {{ $penilaian->pengumpulanTugas->attempt_no }} · Dipublikasikan {{ $penilaian->published_at?->format('d M Y H:i') }}</p>
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-secondary small">Nilai akhir</div><div class="display-5 fw-semibold">{{ $penilaian->nilai_total }}</div><div class="text-secondary small">dari 100</div></div></div></div>
    <div class="col-md-8"><div class="card border-0 shadow-sm h-100"><div class="card-body"><h2 class="h5">Umpan balik pengajar</h2><div class="text-break">{!! nl2br(e($penilaian->feedback ?: 'Tidak ada umpan balik umum.')) !!}</div></div></div></div>
</div>

<div class="card border-0 shadow-sm mb-4"><div class="card-header bg-white"><h2 class="h5 mb-0">Rincian kriteria</h2></div><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Kriteria</th><th>Bobot</th><th>Skor</th><th>Umpan balik</th></tr></thead><tbody>
@foreach($penilaian->detail as $detail)
    <tr><td>{{ $detail->rubrik->nama }}<div class="small text-secondary">{{ $detail->rubrik->deskripsi }}</div></td><td>{{ number_format((float) $detail->rubrik->bobot, 2) }}%</td><td>{{ $detail->skor }} / {{ $detail->rubrik->skor_maksimum }}</td><td>{{ $detail->feedback ?: '—' }}</td></tr>
@endforeach
</tbody></table></div></div>

<div class="card border-0 shadow-sm mb-4"><div class="card-header bg-white"><h2 class="h5 mb-0">Riwayat nilai yang dipublikasikan</h2></div><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Nilai sebelumnya</th><th>Nilai baru</th><th>Waktu</th></tr></thead><tbody>
@forelse($riwayatSiswa as $entry)
    <tr><td>{{ $entry['nilai_sebelumnya'] ?? '—' }}</td><td>{{ $entry['nilai_baru'] }}</td><td>{{ $entry['created_at']?->format('d M Y H:i') }}</td></tr>
@empty
    <tr><td colspan="3" class="text-center text-secondary py-4">Riwayat publikasi belum tersedia.</td></tr>
@endforelse
</tbody></table></div></div>

@if($penilaian->disputasi)
    <div class="card border-0 shadow-sm"><div class="card-body p-4">
        <h2 class="h5">Keberatan nilai</h2>
        <p>Status: <span class="badge text-bg-info">{{ ucfirst($penilaian->disputasi->status) }}</span></p>
        <p><strong>Alasan Anda:</strong><br>{{ $penilaian->disputasi->alasan }}</p>
        @if($penilaian->disputasi->tanggapan_pengajar)
            <p><strong>Tanggapan pengajar:</strong><br>{{ $penilaian->disputasi->tanggapan_pengajar }}</p>
        @endif
        @if($penilaian->disputasi->keputusan)
            <p><strong>Keputusan:</strong><br>{{ $penilaian->disputasi->keputusan }}</p>
        @endif
        <h3 class="h6 mt-4">Perkembangan</h3>
        <ul class="list-group list-group-flush">
            @foreach($penilaian->disputasi->riwayat as $event)
                <li class="list-group-item px-0"><strong>{{ ucfirst($event->status_baru) }}</strong> · {{ $event->created_at?->format('d M Y H:i') }}
                    @if($event->tanggapan)<div>{{ $event->tanggapan }}</div>@endif
                    @if($event->keputusan)<div><strong>Keputusan:</strong> {{ $event->keputusan }}</div>@endif
                </li>
            @endforeach
        </ul>
    </div></div>
@else
    <div class="card border-0 shadow-sm"><div class="card-body p-4">
        <h2 class="h5">Ajukan keberatan</h2>
        <p class="text-secondary">Satu keberatan dapat diajukan untuk nilai ini. Tidak ada batas waktu otomatis pada sistem saat ini.</p>
        <form method="POST" action="{{ route('siswa.nilai.disputasi.store', $penilaian) }}">@csrf
            <label for="alasan" class="form-label">Alasan keberatan</label>
            <textarea id="alasan" name="alasan" rows="4" minlength="10" maxlength="10000" class="form-control mb-3" required>{{ old('alasan') }}</textarea>
            <button class="btn btn-outline-primary" type="submit" onclick="return confirm('Ajukan keberatan untuk nilai ini?')">Ajukan keberatan</button>
        </form>
    </div></div>
@endif
@endsection
