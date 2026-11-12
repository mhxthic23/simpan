@extends('layouts.app')
@section('title', 'Tambah Portfolio · SIMPAN')
@section('content')<a href="{{ route('siswa.portfolio.index') }}" class="text-decoration-none">← Portfolio</a><h1 class="h2 mt-2 mb-4">Tambah karya</h1><form method="POST" enctype="multipart/form-data" action="{{ route('siswa.portfolio.store') }}" class="card border-0 shadow-sm"><div class="card-body p-4">@csrf @include('portfolio._form')</div></form>@endsection
