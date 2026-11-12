@extends('layouts.app')
@section('title', 'Ubah Portfolio · SIMPAN')
@section('content')<a href="{{ route('siswa.portfolio.show', $portfolio) }}" class="text-decoration-none">← Kembali ke karya</a><h1 class="h2 mt-2 mb-4">Ubah portfolio</h1><form method="POST" enctype="multipart/form-data" action="{{ route('siswa.portfolio.update', $portfolio) }}" class="card border-0 shadow-sm"><div class="card-body p-4">@csrf @method('PUT') @include('portfolio._form')</div></form>@endsection
