@extends('layouts.app')
@section('title', 'Pengingat · SIMPAN')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h2 mb-1">Pengingat</h1><p class="text-secondary mb-0">Jadwal pengingat tugas Anda.</p></div><a class="btn btn-outline-primary" href="{{ route('reminder.preferences.edit') }}">Preferensi</a></div>
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Tugas</th><th>Waktu</th><th>Status</th><th></th></tr></thead><tbody>@forelse($reminders as $item)<tr><td>{{ $item->tugas?->judul ?? $item->title }}</td><td>{{ $item->scheduled_at?->format('d M Y H:i') }}</td><td><span class="badge text-bg-secondary">{{ ucfirst($item->status) }}</span></td><td>@if($item->status === 'pending')<form method="POST" action="{{ route('reminder.cancel', $item) }}" onsubmit="return confirm('Batalkan pengingat ini?')">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-danger">Batalkan</button></form>@endif</td></tr>@empty<tr><td colspan="4" class="text-center text-secondary py-5">Belum ada jadwal pengingat.</td></tr>@endforelse</tbody></table></div></div><div class="mt-3">{{ $reminders->links() }}</div>
@endsection
