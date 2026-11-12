<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Penilaian;
use App\Models\User;
use App\Models\VotingDao;
use App\Models\VotingLog;
use App\Services\BlockchainTransactionService;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VotingController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $query = VotingDao::query()->with(['kelas', 'pembuat'])->withCount('logs')->latest('starts_at');
        if ($user->role === 'siswa') $query->whereHas('kelas.siswa', fn (Builder $q) => $q->where('users.id', $user->id))->where('status', '!=', 'draft');
        elseif ($user->role === 'pengajar') $query->whereHas('kelas', fn (Builder $q) => $q->where('pengajar_id', $user->id));
        $votings = $query->paginate(20);
        return view('voting.index', compact('votings'));
    }

    public function create(Request $request): View
    {
        return view('voting.form', ['voting' => new VotingDao(), 'classes' => $request->user()->kelasDiajar()->orderBy('nama')->get(), 'editing' => false]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateProposal($request);
        $class = Kelas::query()->whereKey($data['kelas_id'])->where('pengajar_id', $request->user()->id)->firstOrFail();
        if (! $class->siswa()->where('role', 'siswa')->exists()) throw ValidationException::withMessages(['kelas_id' => 'Kelas belum memiliki siswa yang memenuhi syarat voting.']);
        $options = array_values(array_unique(array_map('trim', $data['options'])));
        if (count($options) < 2) throw ValidationException::withMessages(['options' => 'Proposal harus memiliki setidaknya dua opsi yang berbeda.']);
        $voting = DB::transaction(function () use ($data, $options, $request): VotingDao {
            $voting = VotingDao::create([
                'kelas_id' => $data['kelas_id'], 'penilaian_id' => $data['penilaian_id'] ?? null,
                'dibuat_oleh' => $request->user()->id, 'judul' => $data['judul'], 'deskripsi' => $data['deskripsi'] ?? null,
                'jenis_voting' => $data['jenis_voting'], 'status' => 'draft',
                'starts_at' => $data['starts_at'], 'ends_at' => $data['ends_at'],
            ]);
            foreach ($options as $index => $option) $voting->options()->create(['opsi' => $option, 'urutan' => $index + 1]);
            return $voting;
        });
        return redirect()->route('pengajar.voting.show', $voting)->with('status', 'Proposal disimpan sebagai draft.');
    }

    public function show(Request $request, VotingDao $votingDao): View
    {
        $voting = $this->visibleTo($request->user(), $votingDao)->with(['kelas', 'pembuat', 'options' => fn ($q) => $q->orderBy('urutan')])->withCount('logs')->firstOrFail();
        $hasVoted = $voting->logs()->where('user_id', $request->user()->id)->exists();
        $now = now();
        $canVote = $request->user()->role === 'siswa' && $voting->status === 'active'
            && $now->greaterThanOrEqualTo($voting->starts_at) && $now->lessThanOrEqualTo($voting->ends_at) && ! $hasVoted;
        if ($voting->status === 'closed') $voting->load(['options' => fn ($q) => $q->withCount('logs')->orderBy('urutan')]);
        return view('voting.show', compact('voting', 'hasVoted', 'canVote'));
    }

    public function edit(Request $request, VotingDao $votingDao): View
    {
        $voting = VotingDao::query()->whereKey($votingDao->id)->where('dibuat_oleh', $request->user()->id)->where('status', 'draft')->whereHas('kelas', fn (Builder $q) => $q->where('pengajar_id', $request->user()->id))->with('options')->firstOrFail();
        return view('voting.form', ['voting' => $voting, 'classes' => $request->user()->kelasDiajar()->orderBy('nama')->get(), 'editing' => true]);
    }

    public function update(Request $request, VotingDao $votingDao): RedirectResponse
    {
        $voting = VotingDao::query()->whereKey($votingDao->id)->where('dibuat_oleh', $request->user()->id)->where('status', 'draft')->whereHas('kelas', fn (Builder $q) => $q->where('pengajar_id', $request->user()->id))->firstOrFail();
        $data = $this->validateProposal($request);
        $options = array_values(array_unique(array_map('trim', $data['options'])));
        if (count($options) < 2) throw ValidationException::withMessages(['options' => 'Proposal harus memiliki setidaknya dua opsi yang berbeda.']);
        DB::transaction(function () use ($voting, $data, $options): void {
            $voting->update(['kelas_id' => $data['kelas_id'], 'penilaian_id' => $data['penilaian_id'] ?? null,
                'judul' => $data['judul'], 'deskripsi' => $data['deskripsi'] ?? null, 'jenis_voting' => $data['jenis_voting'],
                'starts_at' => $data['starts_at'], 'ends_at' => $data['ends_at']]);
            $voting->options()->delete();
            foreach ($options as $index => $option) $voting->options()->create(['opsi' => $option, 'urutan' => $index + 1]);
        });
        return redirect()->route('pengajar.voting.show', $voting)->with('status', 'Draft proposal diperbarui.');
    }

    public function publish(Request $request, VotingDao $votingDao): RedirectResponse
    {
        $voting = VotingDao::query()->whereKey($votingDao->id)->where('dibuat_oleh', $request->user()->id)->where('status', 'draft')->whereHas('kelas', fn (Builder $q) => $q->where('pengajar_id', $request->user()->id))->with(['options', 'kelas.siswa'])->firstOrFail();
        if ($voting->options->count() < 2) throw ValidationException::withMessages(['voting' => 'Proposal harus memiliki minimal dua opsi.']);
        if ($voting->ends_at->lessThanOrEqualTo(now())) throw ValidationException::withMessages(['ends_at' => 'Periode voting sudah berakhir.']);
        $voting->forceFill(['status' => 'active'])->save();
        foreach ($voting->kelas->siswa()->where('role', 'siswa')->get() as $student) {
            try { app(NotificationService::class)->sendOnce($student, 'Voting kelas dibuka', 'Voting "'.$voting->judul.'" tersedia untuk kelas Anda.', 'voting_opened', 'voting:'.$voting->id.':open:'.$student->id, $voting); }
            catch (\Throwable $exception) { report($exception); }
        }
        try { app(BlockchainTransactionService::class)->record('voting_opened', $voting, ['status' => 'active', 'starts_at' => $voting->starts_at->toISOString(), 'ends_at' => $voting->ends_at->toISOString()], 'voting:'.$voting->id.':opened'); }
        catch (\Throwable $exception) { report($exception); }
        return back()->with('status', 'Voting dibuka. Pemungutan suara hanya diterima selama periode yang ditentukan.');
    }

    public function vote(Request $request, VotingDao $votingDao): RedirectResponse
    {
        $data = $request->validate(['voting_option_id' => ['required', 'integer', Rule::exists('voting_option', 'id')]], ['voting_option_id.required' => 'Pilih satu opsi.', 'voting_option_id.exists' => 'Opsi voting tidak valid.']);
        try {
            $vote = DB::transaction(function () use ($request, $votingDao, $data): VotingLog {
                $voting = VotingDao::query()->whereKey($votingDao->id)->lockForUpdate()->firstOrFail();
                abort_unless($request->user()->role === 'siswa' && $voting->kelas()->whereHas('siswa', fn (Builder $q) => $q->where('users.id', $request->user()->id))->exists(), 404);
                $now = now();
                if ($voting->status !== 'active' || $now->lessThan($voting->starts_at) || $now->greaterThan($voting->ends_at)) throw ValidationException::withMessages(['voting' => 'Voting belum dimulai atau sudah ditutup.']);
                $option = $voting->options()->whereKey($data['voting_option_id'])->firstOrFail();
                return VotingLog::create(['voting_dao_id' => $voting->id, 'voting_option_id' => $option->id, 'user_id' => $request->user()->id, 'voted_at' => now()]);
            });
        } catch (QueryException) {
            return back()->withErrors(['voting' => 'Akun ini telah memberikan suara pada voting tersebut.']);
        }
        try {
            app(BlockchainTransactionService::class)->record('voting_ballot_cast', $vote, ['voting_id' => $vote->voting_dao_id, 'cast_at' => $vote->voted_at->toISOString()], 'voting-log:'.$vote->id);
            $tokens = (int) config('services.simpan_reputation.voting_participation_token', 0);
            if ($tokens !== 0) app(\App\Services\ReputationLedgerService::class)->record($request->user(), 'voting_participation', $tokens, 'voting-log:'.$vote->id, 'Partisipasi voting', $vote);
        } catch (\Throwable $exception) { report($exception); }
        return redirect()->route('siswa.voting.show', $votingDao)->with('status', 'Suara Anda berhasil dicatat. Pilihan tidak ditampilkan kepada pengguna lain.');
    }

    private function visibleTo(User $user, VotingDao $voting): Builder
    {
        $query = VotingDao::query()->whereKey($voting->id);
        if ($user->role === 'admin') return $query;
        if ($user->role === 'siswa') return $query->where('status', '!=', 'draft')->whereHas('kelas.siswa', fn (Builder $q) => $q->where('users.id', $user->id));
        return $query->whereHas('kelas', fn (Builder $q) => $q->where('pengajar_id', $user->id));
    }

    private function validateProposal(Request $request): array
    {
        $data = $request->validate([
            'kelas_id' => ['required', 'integer', Rule::exists('kelas', 'id')->where('pengajar_id', $request->user()->id)],
            'penilaian_id' => ['nullable', 'integer', Rule::exists('penilaian', 'id')],
            'judul' => ['required', 'string', 'max:255'], 'deskripsi' => ['nullable', 'string', 'max:10000'],
            'jenis_voting' => ['required', 'string', 'max:50'],
            'starts_at' => ['required', 'date', 'after_or_equal:now'], 'ends_at' => ['required', 'date', 'after:starts_at'],
            'options' => ['required', 'array', 'min:2', 'max:10'], 'options.*' => ['required', 'string', 'max:255', 'distinct'],
        ], ['kelas_id.exists' => 'Kelas tidak ditemukan atau bukan kelas yang Anda ajar.', 'judul.required' => 'Judul proposal wajib diisi.',
            'starts_at.after_or_equal' => 'Waktu mulai tidak boleh di masa lalu.', 'ends_at.after' => 'Waktu selesai harus setelah waktu mulai.',
            'options.min' => 'Tambahkan setidaknya dua opsi.', 'options.max' => 'Maksimal sepuluh opsi.']);
        if (! empty($data['penilaian_id'])) Penilaian::query()->whereKey($data['penilaian_id'])
            ->whereHas('pengumpulanTugas.tugas', fn (Builder $q) => $q->where('kelas_id', $data['kelas_id']))->firstOrFail();
        return $data;
    }
}
