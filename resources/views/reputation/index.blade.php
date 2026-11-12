@extends('layouts.app')
@section('title', 'Reputasi · SIMPAN')
@section('content')
<h1 class="h2 mb-1">Reputasi akademik</h1><p class="text-secondary mb-4">Ledger poin internal. Poin bukan aset kripto dan tidak memiliki nilai uang.</p>
<div class="card border-0 shadow-sm mb-4"><div class="card-body"><div class="small text-secondary">Saldo kumulatif</div><div class="display-5 fw-semibold">{{ $balance }}</div></div></div>
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Waktu</th><th>Aktivitas</th><th>Keterangan</th><th>Perubahan</th></tr></thead><tbody>@forelse($entries as $item)<tr><td>{{ $item->created_at?->format('d M Y H:i') }}</td><td>{{ str_replace('_', ' ', ucfirst($item->jenis_aktivitas)) }}</td><td>{{ $item->keterangan ?: '—' }}</td><td class="{{ $item->jumlah_token >= 0 ? 'text-success' : 'text-danger' }}">{{ $item->jumlah_token > 0 ? '+' : '' }}{{ $item->jumlah_token }}</td></tr>@empty<tr><td colspan="4" class="text-center text-secondary py-5">Belum ada aktivitas reputasi.</td></tr>@endforelse</tbody></table></div></div><div class="mt-3">{{ $entries->links() }}</div>
@endsection
