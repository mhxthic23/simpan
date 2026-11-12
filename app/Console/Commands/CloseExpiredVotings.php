<?php

namespace App\Console\Commands;

use App\Models\VotingDao;
use App\Services\BlockchainTransactionService;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class CloseExpiredVotings extends Command
{
    protected $signature = 'simpan:close-votings';
    protected $description = 'Menutup voting SIMPAN yang melewati waktu berakhir.';

    public function handle(): int
    {
        VotingDao::query()->where('status', 'active')->where('ends_at', '<=', now())->chunkById(100, function ($votings): void {
            foreach ($votings as $voting) {
                DB::transaction(function () use ($voting): void {
                    $locked = VotingDao::query()->whereKey($voting->id)->with('kelas.siswa')->lockForUpdate()->first();
                    if (! $locked || $locked->status !== 'active' || $locked->ends_at->isFuture()) return;
                    $locked->forceFill(['status' => 'closed'])->save();
                    try {
                        app(BlockchainTransactionService::class)->record('voting_closed', $locked, ['status' => 'closed', 'ended_at' => $locked->ends_at->toISOString()], 'voting:'.$locked->id.':closed');
                        foreach ($locked->kelas->siswa()->where('role', 'siswa')->get() as $student) {
                            app(NotificationService::class)->sendOnce($student, 'Voting ditutup', 'Voting "'.$locked->judul.'" telah ditutup. Hasil kini tersedia.', 'voting_closed', 'voting:'.$locked->id.':closed:'.$student->id, $locked);
                        }
                    } catch (Throwable $exception) { report($exception); }
                });
            }
        });
        return self::SUCCESS;
    }
}
