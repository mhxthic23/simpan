@extends('layouts.app')

@section('title', 'Fakultas · SIMPAN')

@section('content')
@include('admin.akademik._nav')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div><h1 class="h2 mb-1">Manajemen Fakultas</h1><p class="text-secondary mb-0">Kelola unit fakultas dan jurusan yang bernaung di dalamnya.</p></div>
    <a class="btn btn-primary" href="{{ route('admin.fakultas.create') }}">Tambah fakultas</a>
</div>
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="GET" class="card card-body border-0 shadow-sm mb-4">
    <div class="row g-2"><div class="col-md-9"><label class="form-label" for="q">Cari fakultas</label><input id="q" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}"></div>
    <div class="col-md-3 d-flex align-items-end gap-2"><button class="btn btn-outline-primary" type="submit">Cari</button><a class="btn btn-outline-secondary" href="{{ route('admin.fakultas.index') }}">Reset</a></div></div>
</form>
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead class="table-light"><tr><th>Nama fakultas</th><th>Jumlah jurusan</th><th class="text-end">Tindakan</th></tr></thead>
    <tbody>@forelse($fakultas as $item)<tr><td>{{ $item->nama }}</td><td>{{ $item->jurusan_count }}</td><td class="text-end">
        <div class="d-inline-flex gap-2"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.fakultas.edit', $item) }}">Ubah</a>
        <form method="POST" action="{{ route('admin.fakultas.destroy', $item) }}" onsubmit="return confirm('Hapus fakultas {{ $item->nama }}? Fakultas yang masih memiliki jurusan tidak dapat dihapus.');">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button></form></div>
    </td></tr>@empty<tr><td colspan="3" class="text-center text-secondary py-4">Belum ada fakultas.</td></tr>@endforelse</tbody>
</table></div></div>
<div class="mt-3">{{ $fakultas->links('pagination::bootstrap-5') }}</div>
@endsection
