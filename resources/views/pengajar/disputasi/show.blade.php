@extends('layouts.app')

@section('title', 'Tinjau Disputasi · SIMPAN')

@section('content')
<a class="text-decoration-none" href="{{ route('pengajar.disputasi.index') }}">← Kembali ke daftar</a>
<div class="d-flex flex-wrap justify-content-between align-items-start mt-2 mb-4">
    <div><h1 class="h2 mb-1">Tinjau keberatan nilai</h1><p class="text-secondary mb-0">{{ $tugas->judul }} · {{ $disputasi->siswa->name }}</p></div>
    <span class="badge text-bg-info">{{ ucfirst($disputasi->status) }}</span>
</div>
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm mb-4"><div class="card-body">
            <h2 class="h5">Alasan siswa</h2><p class="mb-2">{{ $disputasi->alasan }}</p>
            <div class="small text-secondary">Diajukan {{ $disputasi->submitted_at?->format('d M Y H:i') }}</div><hr>
            <h3 class="h6">Nilai saat ini: {{ $disputasi->penilaian->nilai_total }} / 100</h3>
            <p class="mb-0">{{ $disputasi->penilaian->feedback ?: 'Tidak ada umpan balik umum.' }}</p>
        </div></div>
        <div class="card border-0 shadow-sm"><div class="card-body">
            <h2 class="h5">Riwayat penanganan</h2>
            <ol class="list-group list-group-numbered list-group-flush">
                @forelse($disputasi->riwayat as $event)
                    <li class="list-group-item px-0"><strong>{{ ucfirst($event->status_baru) }}</strong> · {{ $event->created_at?->format('d M Y H:i') }}
                        <div class="small text-secondary">oleh {{ $event->pengubah->name }}</div>
                        @if($event->tanggapan)<div>{{ $event->tanggapan }}</div>@endif
                        @if($event->keputusan)<div><strong>Keputusan:</strong> {{ $event->keputusan }}</div>@endif
                    </li>
                @empty
                    <li class="list-group-item px-0 text-secondary">Belum ada perubahan.</li>
                @endforelse
            </ol>
        </div></div>
    </div>

    <div class="col-lg-7">
        @if(in_array($disputasi->status, ['pending', 'reviewing'], true))
            <form method="POST" action="{{ route('pengajar.disputasi.review', $disputasi) }}" class="card border-0 shadow-sm mb-4"><div class="card-body">
                <h2 class="h5">Tanggapan peninjauan</h2>@csrf
                <label class="form-label" for="review-response">Tanggapan untuk siswa (opsional)</label>
                <textarea id="review-response" name="tanggapan_pengajar" rows="3" maxlength="10000" class="form-control mb-3">{{ old('tanggapan_pengajar', $disputasi->tanggapan_pengajar) }}</textarea>
                <button class="btn btn-outline-primary">Simpan tanggapan dan tandai ditinjau</button>
            </div></form>

            <form method="POST" action="{{ route('pengajar.disputasi.resolve', $disputasi) }}" class="card border-0 shadow-sm"><div class="card-body">
                <h2 class="h5">Keputusan akhir</h2>@csrf
                <div class="mb-3"><label for="decision-type" class="form-label">Hasil</label><select id="decision-type" name="keputusan_tipe" class="form-select" required><option value="rejected" @selected(old('keputusan_tipe') === 'rejected')>Tolak keberatan</option><option value="accepted" @selected(old('keputusan_tipe') === 'accepted')>Terima dan perbarui nilai</option></select></div>
                <div class="mb-3"><label for="decision" class="form-label">Alasan keputusan</label><textarea id="decision" name="keputusan" rows="3" minlength="5" maxlength="10000" class="form-control" required>{{ old('keputusan') }}</textarea></div>
                <div class="mb-3"><label for="response" class="form-label">Tanggapan untuk siswa (opsional)</label><textarea id="response" name="tanggapan_pengajar" rows="2" maxlength="10000" class="form-control">{{ old('tanggapan_pengajar', $disputasi->tanggapan_pengajar) }}</textarea></div>
                <div class="alert alert-light border">Jika keberatan diterima, isi skor baru. Jika ditolak, skor tidak berubah. Nilai akhir selalu dihitung server.</div>
                <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Kriteria</th><th>Bobot</th><th>Skor baru</th><th>Komentar kriteria</th></tr></thead><tbody>
                    @foreach($criteria as $criterion)
                        @php($detail = $currentDetails->get($criterion->id))
                        <tr><td>{{ $criterion->nama }}<div class="small text-secondary">Maks. {{ $criterion->skor_maksimum }}</div></td><td>{{ $criterion->bobot }}%</td><td><input name="scores[{{ $criterion->id }}]" type="number" min="0" max="{{ $criterion->skor_maksimum }}" step="0.01" value="{{ old('scores.'.$criterion->id, $detail?->skor) }}" class="form-control"></td><td><textarea name="detail_feedback[{{ $criterion->id }}]" rows="2" class="form-control">{{ old('detail_feedback.'.$criterion->id, $detail?->feedback) }}</textarea></td></tr>
                    @endforeach
                </tbody></table></div>
                <div class="mb-3"><label for="grade-feedback" class="form-label">Umpan balik umum (opsional)</label><textarea id="grade-feedback" name="feedback" rows="3" class="form-control">{{ old('feedback', $disputasi->penilaian->feedback) }}</textarea></div>
                <button class="btn btn-primary" onclick="return confirm('Selesaikan disputasi dengan keputusan ini?')">Simpan keputusan</button>
            </div></form>
        @else
            <div class="alert alert-secondary">Disputasi ini telah diselesaikan pada {{ $disputasi->resolved_at?->format('d M Y H:i') }}. Riwayat bersifat baca-saja.</div>
            @if($disputasi->keputusan)
                <div class="card border-0 shadow-sm"><div class="card-body"><h2 class="h5">Keputusan</h2><p>{{ $disputasi->keputusan }}</p>
                    @if($disputasi->tanggapan_pengajar)<h3 class="h6">Tanggapan</h3><p class="mb-0">{{ $disputasi->tanggapan_pengajar }}</p>@endif
                </div></div>
            @endif
        @endif
    </div>
</div>
@endsection
