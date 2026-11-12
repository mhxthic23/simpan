@extends('layouts.app')
@section('title', $listing->nama.' · Marketplace SIMPAN')
@section('content')
<a href="{{ route('marketplace.index') }}" class="text-decoration-none">← Marketplace</a><div class="d-flex justify-content-between align-items-start mt-2 mb-4"><div><h1 class="h2 mb-1">{{ $listing->nama }}</h1><p class="text-secondary mb-0">{{ $listing->kategori ?: 'Tanpa kategori' }} · oleh {{ $listing->pengajar->name }}</p></div><span class="badge text-bg-{{ $listing->is_verified ? 'primary' : 'secondary' }}">{{ $listing->is_verified ? 'Terverifikasi' : 'Belum diverifikasi' }}</span></div>
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="card border-0 shadow-sm"><div class="card-body p-4"><p>{{ $listing->deskripsi }}</p><div class="table-responsive"><table class="table"><thead><tr><th>#</th><th>Kriteria</th><th>Indikator</th><th>Bobot</th><th>Skor maksimum</th></tr></thead><tbody>@foreach($listing->struktur_rubrik as $index => $item)<tr><td>{{ $index + 1 }}</td><td>{{ $item['nama'] }}</td><td>{{ $item['deskripsi'] ?? '—' }}</td><td>{{ number_format((float)$item['bobot'], 2) }}%</td><td>{{ $item['skor_maksimum'] }}</td></tr>@endforeach</tbody></table></div>
@if(auth()->user()->role === 'pengajar')
    @if($listing->pengajar_id === auth()->id() && $listing->status !== 'published')<form method="POST" action="{{ route('pengajar.marketplace.publish', $listing) }}" class="d-inline">@csrf<button class="btn btn-outline-primary">Ajukan untuk diterbitkan</button></form>@endif
    @if($listing->status === 'published')<form method="POST" action="{{ route('pengajar.marketplace.copy', $listing) }}" class="mt-3"><div class="row g-2"><div class="col-md-7"><select name="tugas_id" class="form-select" required><option value="">Pilih tugas yang belum memiliki rubrik</option>@foreach($tasks as $task)<option value="{{ $task->id }}">{{ $task->judul }}</option>@endforeach</select></div><div class="col-auto">@csrf<button class="btn btn-primary">Salin ke tugas</button></div></div></form>@endif
@endif
@if(auth()->user()->role === 'admin' && $listing->status === 'published')<form method="POST" action="{{ route('admin.marketplace.verify', $listing) }}" class="mt-3">@csrf @method('PATCH')<button class="btn btn-outline-primary">{{ $listing->is_verified ? 'Cabut verifikasi' : 'Verifikasi template' }}</button></form>@endif
</div></div>
@endsection
