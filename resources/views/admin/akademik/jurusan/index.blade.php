@extends('layouts.app')

@section('title', 'Jurusan · SIMPAN')

@section('content')
@include('admin.akademik._nav')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div><h1 class="h2 mb-1">Manajemen Jurusan</h1><p class="text-secondary mb-0">Atur jurusan dan fakultas induknya.</p></div><a class="btn btn-primary" href="{{ route('admin.jurusan.create') }}">Tambah jurusan</a></div>
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="GET" class="card card-body border-0 shadow-sm mb-4"><div class="row g-3 align-items-end">
    <div class="col-md-6"><label for="q" class="form-label">Cari jurusan</label><input id="q" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}"></div>
    <div class="col-md-4"><label for="fakultas_id" class="form-label">Filter fakultas</label><select id="fakultas_id" name="fakultas_id" class="form-select"><option value="">Semua fakultas</option>@foreach($fakultasList as $fakultas)<option value="{{ $fakultas->id }}" @selected((string)($filters['fakultas_id'] ?? '') === (string)$fakultas->id)>{{ $fakultas->nama }}</option>@endforeach</select></div>
    <div class="col-md-2 d-flex gap-2"><button class="btn btn-outline-primary" type="submit">Cari</button><a class="btn btn-outline-secondary" href="{{ route('admin.jurusan.index') }}">Reset</a></div>
</div></form>
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Nama jurusan</th><th>Fakultas</th><th>Jumlah kelas</th><th class="text-end">Tindakan</th></tr></thead>
<tbody>@forelse($jurusan as $item)<tr><td>{{ $item->nama }}</td><td>{{ $item->fakultas->nama }}</td><td>{{ $item->kelas_count }}</td><td class="text-end"><div class="d-inline-flex gap-2"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.jurusan.edit', $item) }}">Ubah</a><form method="POST" action="{{ route('admin.jurusan.destroy', $item) }}" onsubmit="return confirm('Hapus jurusan {{ $item->nama }}? Jurusan dengan kelas terkait tidak dapat dihapus.');">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button></form></div></td></tr>@empty<tr><td colspan="4" class="text-center text-secondary py-4">Belum ada jurusan.</td></tr>@endforelse</tbody>
</table></div></div><div class="mt-3">{{ $jurusan->links('pagination::bootstrap-5') }}</div>
@endsection
