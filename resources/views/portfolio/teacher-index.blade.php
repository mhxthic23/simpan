@extends('layouts.app')

@section('title', 'Portfolio siswa · SIMPAN')

@section('content')
<div class="d-flex justify-content-between align-items-start mb-4">
    <div><span class="badge text-bg-success mb-2">Area pengajar</span><h1 class="h2">Portfolio siswa</h1><p class="text-secondary mb-0">Karya publik siswa yang terdaftar di kelas yang Anda ajar.</p></div>
</div>
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead class="table-light"><tr><th>Nama siswa</th><th>Email</th><th>Karya publik</th><th></th></tr></thead>
    <tbody>
    @forelse($students as $student)
        <tr><td>{{ $student->name }}</td><td>{{ $student->email }}</td><td>{{ $student->public_portfolios_count }}</td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('pengajar.portfolio.student.show', $student) }}">Lihat portfolio</a></td></tr>
    @empty
        <tr><td colspan="4" class="text-center text-secondary py-4">Belum ada portfolio publik dari siswa di kelas Anda.</td></tr>
    @endforelse
    </tbody>
</table></div><div class="card-body">{{ $students->links() }}</div></div>
@endsection
