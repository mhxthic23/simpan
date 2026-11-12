@extends('layouts.app')

@section('title', 'Tambah Jurusan · SIMPAN')

@section('content')
@include('admin.akademik._nav')
<div class="row justify-content-center"><div class="col-lg-7"><a href="{{ route('admin.jurusan.index') }}" class="text-decoration-none">← Kembali ke jurusan</a><h1 class="h2 mt-2 mb-4">Tambah Jurusan</h1>
<div class="card border-0 shadow-sm"><div class="card-body p-4">@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('admin.jurusan.store') }}">@csrf
    <div class="mb-3"><label for="fakultas_id" class="form-label">Fakultas</label><select id="fakultas_id" name="fakultas_id" class="form-select @error('fakultas_id') is-invalid @enderror" required><option value="">Pilih fakultas</option>@foreach($fakultasList as $fakultas)<option value="{{ $fakultas->id }}" @selected((string)old('fakultas_id') === (string)$fakultas->id)>{{ $fakultas->nama }}</option>@endforeach</select>@error('fakultas_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="mb-4"><label for="nama" class="form-label">Nama jurusan</label><input id="nama" name="nama" maxlength="150" value="{{ old('nama') }}" class="form-control @error('nama') is-invalid @enderror" required>@error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <button class="btn btn-primary" type="submit">Simpan jurusan</button>
</form></div></div></div></div>
@endsection
