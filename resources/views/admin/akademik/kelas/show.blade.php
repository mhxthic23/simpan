@extends('layouts.app')

@section('title', 'Detail Kelas · SIMPAN')

@section('content')
@include('admin.akademik._nav')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div><a href="{{ route('admin.kelas.index') }}" class="text-decoration-none">← Kembali ke kelas</a><h1 class="h2 mt-2 mb-1">{{ $kelas->nama }}</h1><p class="text-secondary mb-0">{{ $kelas->jurusan->nama }} · {{ $kelas->jurusan->fakultas->nama }} · Pengajar: {{ $kelas->pengajar->name }}</p></div><a class="btn btn-outline-primary" href="{{ route('admin.kelas.edit', $kelas) }}">Ubah kelas</a></div>
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="card border-0 shadow-sm mb-4"><div class="card-body p-4"><h2 class="h5">Tambahkan siswa</h2>
    @if($siswaTersedia->isEmpty())<p class="text-secondary mb-0">Tidak ada siswa aktif yang belum terdaftar di kelas ini.</p>@else
    <form method="POST" action="{{ route('admin.kelas.students.store', $kelas) }}" class="row g-2 align-items-end">@csrf
        <div class="col-md-9"><label class="form-label" for="siswa_id">Siswa</label><select id="siswa_id" name="siswa_id" class="form-select" required><option value="">Pilih siswa</option>@foreach($siswaTersedia as $siswa)<option value="{{ $siswa->id }}">{{ $siswa->name }} — {{ $siswa->email }}</option>@endforeach</select></div>
        <div class="col-md-3"><button class="btn btn-primary" type="submit">Tambahkan ke kelas</button></div>
    </form>@endif
</div></div>
<div class="card border-0 shadow-sm"><div class="card-header bg-white py-3"><h2 class="h5 mb-0">Siswa dalam kelas <span class="badge text-bg-secondary">{{ $kelas->siswa->count() }}</span></h2></div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Nama</th><th>Email</th><th>Pindahkan ke kelas</th><th class="text-end">Tindakan</th></tr></thead><tbody>
@forelse($kelas->siswa as $siswa)<tr><td>{{ $siswa->name }}</td><td>{{ $siswa->email }}</td><td>@if($kelasTujuan->isNotEmpty())<form method="POST" action="{{ route('admin.kelas.students.move', [$kelas, $siswa]) }}" class="d-flex gap-2">@csrf<select name="kelas_tujuan_id" class="form-select form-select-sm" aria-label="Kelas tujuan untuk {{ $siswa->name }}" required><option value="">Pilih kelas tujuan</option>@foreach($kelasTujuan as $tujuan)<option value="{{ $tujuan->id }}">{{ $tujuan->nama }} — {{ $tujuan->jurusan->nama }}</option>@endforeach</select><button class="btn btn-sm btn-outline-primary" type="submit" onclick="return confirm('Pindahkan {{ $siswa->name }} ke kelas yang dipilih?');">Pindahkan</button></form>@else<span class="text-secondary small">Belum ada kelas tujuan lain.</span>@endif</td><td class="text-end"><form method="POST" action="{{ route('admin.kelas.students.destroy', [$kelas, $siswa]) }}" onsubmit="return confirm('Keluarkan {{ $siswa->name }} dari kelas ini?');">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit">Keluarkan</button></form></td></tr>@empty<tr><td colspan="4" class="text-center text-secondary py-4">Belum ada siswa di kelas ini.</td></tr>@endforelse
</tbody></table></div></div>
@endsection
