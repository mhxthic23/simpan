@extends('layouts.app')

@section('title', 'Kelas · SIMPAN')

@section('content')
@include('admin.akademik._nav')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div><h1 class="h2 mb-1">Manajemen Kelas</h1><p class="text-secondary mb-0">Kelola kelas, jurusan, pengajar, dan keanggotaannya.</p></div><a class="btn btn-primary" href="{{ route('admin.kelas.create') }}">Tambah kelas</a></div>
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="GET" class="card card-body border-0 shadow-sm mb-4"><div class="row g-3 align-items-end">
    <div class="col-lg-4"><label for="q" class="form-label">Cari kelas</label><input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control"></div>
    <div class="col-lg-3"><label for="jurusan_id" class="form-label">Jurusan</label><select id="jurusan_id" name="jurusan_id" class="form-select"><option value="">Semua jurusan</option>@foreach($jurusanList as $jurusan)<option value="{{ $jurusan->id }}" @selected((string)($filters['jurusan_id'] ?? '') === (string)$jurusan->id)>{{ $jurusan->nama }} — {{ $jurusan->fakultas->nama }}</option>@endforeach</select></div>
    <div class="col-lg-3"><label for="pengajar_id" class="form-label">Pengajar</label><select id="pengajar_id" name="pengajar_id" class="form-select"><option value="">Semua pengajar</option>@foreach($pengajarList as $pengajar)<option value="{{ $pengajar->id }}" @selected((string)($filters['pengajar_id'] ?? '') === (string)$pengajar->id)>{{ $pengajar->name }}</option>@endforeach</select></div>
    <div class="col-lg-2 d-flex gap-2"><button class="btn btn-outline-primary" type="submit">Cari</button><a class="btn btn-outline-secondary" href="{{ route('admin.kelas.index') }}">Reset</a></div>
</div></form>
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Kelas</th><th>Jurusan / Fakultas</th><th>Pengajar</th><th>Jumlah siswa</th><th class="text-end">Tindakan</th></tr></thead>
<tbody>@forelse($kelas as $item)<tr><td>{{ $item->nama }}</td><td>{{ $item->jurusan->nama }}<div class="small text-secondary">{{ $item->jurusan->fakultas->nama }}</div></td><td>{{ $item->pengajar->name }}</td><td>{{ $item->siswa_count }}</td><td class="text-end"><div class="d-inline-flex gap-2"><a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.kelas.show', $item) }}">Detail</a><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.kelas.edit', $item) }}">Ubah</a><form method="POST" action="{{ route('admin.kelas.destroy', $item) }}" onsubmit="return confirm('Hapus kelas {{ $item->nama }}? Keanggotaan siswa akan dihapus; kelas dengan tugas/aktivitas akademik tidak dapat dihapus.');">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button></form></div></td></tr>@empty<tr><td colspan="5" class="text-center text-secondary py-4">Belum ada kelas.</td></tr>@endforelse</tbody>
</table></div></div><div class="mt-3">{{ $kelas->links('pagination::bootstrap-5') }}</div>
@endsection
