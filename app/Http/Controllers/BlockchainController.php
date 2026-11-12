<?php

namespace App\Http\Controllers;

use App\Models\BlockchainTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BlockchainController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'submitted', 'confirmed', 'failed'])],
            'event_type' => ['nullable', 'string', 'max:50'],
        ], ['status.in' => 'Status transaksi tidak valid.']);
        $transactions = $this->visibleTo($request->user())
            ->when($filters['status'] ?? null, fn (Builder $q, $status) => $q->where('status', $status))
            ->when($filters['event_type'] ?? null, fn (Builder $q, $type) => $q->where('event_type', $type))
            ->latest()->paginate(25)->withQueryString();
        return view('blockchain.index', compact('transactions', 'filters'));
    }

    public function show(Request $request, BlockchainTransaction $transaction): View
    {
        $transaction = $this->visibleTo($request->user())->whereKey($transaction->id)->firstOrFail();
        $explorerUrl = $transaction->transaction_hash
            ? rtrim((string) config('services.simpan_blockchain.explorer_url'), '/').'/'.$transaction->transaction_hash
            : null;
        return view('blockchain.show', compact('transaction', 'explorerUrl'));
    }

    private function visibleTo(User $user): Builder
    {
        $query = BlockchainTransaction::query();
        if ($user->role === 'admin') return $query;
        if ($user->role === 'siswa') {
            return $query->where(function (Builder $scope) use ($user): void {
                $scope->where(fn (Builder $q) => $q->where('reference_type', \App\Models\PengumpulanTugas::class)->whereHasMorph('reference', [\App\Models\PengumpulanTugas::class], fn (Builder $r) => $r->where('siswa_id', $user->id)))
                    ->orWhere(fn (Builder $q) => $q->where('reference_type', \App\Models\Penilaian::class)->whereHasMorph('reference', [\App\Models\Penilaian::class], fn (Builder $r) => $r->whereHas('pengumpulanTugas', fn (Builder $s) => $s->where('siswa_id', $user->id))))
                    ->orWhere(fn (Builder $q) => $q->where('reference_type', \App\Models\Disputasi::class)->whereHasMorph('reference', [\App\Models\Disputasi::class], fn (Builder $r) => $r->where('siswa_id', $user->id)))
                    ->orWhere(fn (Builder $q) => $q->where('reference_type', \App\Models\VotingLog::class)->whereHasMorph('reference', [\App\Models\VotingLog::class], fn (Builder $r) => $r->where('user_id', $user->id)));
            });
        }
        return $query->where(function (Builder $scope) use ($user): void {
            foreach ([\App\Models\PengumpulanTugas::class, \App\Models\Penilaian::class, \App\Models\Disputasi::class, \App\Models\VotingDao::class, \App\Models\VotingLog::class] as $model) {
                $scope->orWhere(fn (Builder $q) => $q->where('reference_type', $model)->whereHasMorph('reference', [$model], function (Builder $ref) use ($model, $user): void {
                    if ($model === \App\Models\PengumpulanTugas::class) $ref->whereHas('tugas.kelas', fn (Builder $c) => $c->where('pengajar_id', $user->id));
                    elseif ($model === \App\Models\Penilaian::class) $ref->whereHas('pengumpulanTugas.tugas.kelas', fn (Builder $c) => $c->where('pengajar_id', $user->id));
                    elseif ($model === \App\Models\Disputasi::class) $ref->whereHas('penilaian.pengumpulanTugas.tugas.kelas', fn (Builder $c) => $c->where('pengajar_id', $user->id));
                    elseif ($model === \App\Models\VotingDao::class) $ref->whereHas('kelas', fn (Builder $c) => $c->where('pengajar_id', $user->id));
                    else $ref->whereHas('votingDao.kelas', fn (Builder $c) => $c->where('pengajar_id', $user->id));
                }));
            }
        });
    }
}
