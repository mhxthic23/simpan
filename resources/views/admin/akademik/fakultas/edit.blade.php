@extends('layouts.app')

@section('title', 'Ubah Fakultas · SIMPAN')

@section('content')
@include('admin.akademik._nav')
<div class="row justify-content-center"><div class="col-lg-7">
    <a href="{{ route('admin.fakultas.index') }}" class="text-decoration-none">← Kembali ke fakultas</a><h1 class="h2 mt-2 mb-4">Ubah Fakultas</h1>
    <div class="card border-0 shadow-sm"><div class="card-body p-4">
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form method="POST" action="{{ route('admin.fakultas.update', $fakultas) }}">@csrf @method('PUT')
            <label for="nama" class="form-label">Nama fakultas</label><input id="nama" name="nama" maxlength="150" value="{{ old('nama', $fakultas->nama) }}" class="form-control @error('nama') is-invalid @enderror" required>@error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <button class="btn btn-primary mt-4" type="submit">Simpan perubahan</button>
        </form>
    </div></div>
</div></div>
@endsection
