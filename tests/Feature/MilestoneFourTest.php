<?php

namespace Tests\Feature;

use App\Contracts\AiWritingDetector;
use App\Contracts\BlockchainAdapter;
use App\Contracts\IpfsAdapter;
use App\Contracts\PlagiarismChecker;
use App\Jobs\RunSubmissionDiagnostic;
use App\Jobs\SubmitBlockchainTransaction;
use App\Models\Disputasi;
use App\Models\BlockchainTransaction;
use App\Models\Fakultas;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\Notifikasi;
use App\Models\PengumpulanTugas;
use App\Models\Portfolio;
use App\Models\Reminder;
use App\Models\ReminderPreferences;
use App\Models\RubrikMarketplace;
use App\Models\SelfDiagnosticLog;
use App\Models\Tugas;
use App\Models\User;
use App\Models\VotingDao;
use App\Services\BlockchainTransactionService;
use App\Services\NotificationService;
use App\Services\ReputationLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MilestoneFourTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_are_idempotent_private_to_recipient_and_markable_as_read(): void
    {
        $owner = $this->user('siswa');
        $other = $this->user('siswa');
        $service = app(NotificationService::class);
        $first = $service->sendOnce($owner, 'Nilai baru', 'Nilai tersedia.', 'grade_published', 'assessment:1:published');
        $again = $service->sendOnce($owner, 'Berbeda', 'Tidak boleh duplikat.', 'grade_published', 'assessment:1:published');
        $service->sendOnce($other, 'Notifikasi lain', 'Khusus akun kedua.', 'notice', 'notice:2');
        $this->assertSame($first->id, $again->id);
        $this->assertDatabaseCount('notifikasi', 2);

        $this->actingAs($other)->get(route('notifikasi.index'))->assertOk()->assertSee('Khusus akun kedua')->assertDontSee('Nilai tersedia');
        $this->patch(route('notifikasi.read', $first))->assertNotFound();
        $this->actingAs($owner)->patch(route('notifikasi.read', $first))->assertRedirect();
        $this->assertDatabaseHas('notifikasi', ['id' => $first->id, 'is_read' => 1]);
        $this->actingAs($owner)->patch(route('notifikasi.read-all'))->assertRedirect();
    }

    public function test_task_reminders_are_generated_deduplicated_and_dispatched_in_app(): void
    {
        $teacher = $this->user('pengajar');
        $student = $this->user('siswa');
        $class = $this->classFor($teacher);
        $task = $this->taskFor($teacher, $class);
        $this->enroll($student, $class);
        ReminderPreferences::create(['user_id' => $student->id, 'enabled' => true, 'channel' => 'in_app', 'reminder_before_minutes' => [60, 15]]);

        $this->artisan('simpan:generate-reminders')->assertExitCode(0);
        $this->artisan('simpan:generate-reminders')->assertExitCode(0);
        $this->assertDatabaseCount('reminder', 2);
        $reminder = Reminder::where('tugas_id', $task->id)->firstOrFail();
        $reminder->forceFill(['scheduled_at' => now()->subMinute()])->save();
        $this->artisan('simpan:dispatch-reminders')->assertExitCode(0);
        $this->artisan('simpan:dispatch-reminders')->assertExitCode(0);
        $this->assertSame('sent', $reminder->fresh()->status);
        $this->assertDatabaseCount('notifikasi', 1);
        $this->actingAs($student)->get(route('reminder.index'))->assertOk();
        $this->actingAs($student)->get(route('reminder.preferences.edit'))->assertOk();
    }

    public function test_due_reminders_are_cancelled_when_user_disables_preferences(): void
    {
        $teacher = $this->user('pengajar');
        $student = $this->user('siswa');
        $class = $this->classFor($teacher);
        $task = $this->taskFor($teacher, $class);
        $this->enroll($student, $class);
        $preferences = ReminderPreferences::create(['user_id' => $student->id, 'enabled' => true, 'channel' => 'in_app', 'reminder_before_minutes' => [60]]);
        $this->artisan('simpan:generate-reminders')->assertExitCode(0);
        Reminder::query()->where('user_id', $student->id)->update(['scheduled_at' => now()->subMinute()]);
        $preferences->forceFill(['enabled' => false])->save();

        $this->artisan('simpan:dispatch-reminders')->assertExitCode(0);
        $this->assertDatabaseHas('reminder', ['user_id' => $student->id, 'tugas_id' => $task->id, 'status' => 'cancelled']);
        $this->assertDatabaseCount('notifikasi', 0);
    }

    public function test_academic_followup_reminders_are_optional_and_deduplicated(): void
    {
        Config::set('services.simpan_reminders.followup_hours', 2);
        $teacher = $this->user('pengajar');
        $student = $this->user('siswa');
        $class = $this->classFor($teacher);
        $task = $this->taskFor($teacher, $class);
        ReminderPreferences::create(['user_id' => $teacher->id, 'enabled' => true, 'channel' => 'in_app']);
        $submission = PengumpulanTugas::create(['tugas_id' => $task->id, 'siswa_id' => $student->id, 'attempt_no' => 1,
            'submitted_at' => now()->subHours(3), 'status' => 'submitted']);

        $this->artisan('simpan:generate-followup-reminders')->assertExitCode(0);
        $this->artisan('simpan:generate-followup-reminders')->assertExitCode(0);
        $this->assertDatabaseCount('reminder', 1);
        $this->assertDatabaseHas('reminder', ['user_id' => $teacher->id, 'title' => 'Penilaian tertunda #'.$submission->id]);
    }

    public function test_portfolio_privacy_owner_controls_and_teacher_scope(): void
    {
        Storage::fake('local');
        $teacher = $this->user('pengajar');
        $student = $this->user('siswa');
        $outsider = $this->user('siswa');
        $class = $this->classFor($teacher);
        $this->enroll($student, $class);
        $response = $this->actingAs($student)->post(route('siswa.portfolio.store'), [
            'judul' => 'Karya privat', 'deskripsi' => 'Belum dipublikasikan.', 'is_public' => 0,
            'file' => UploadedFile::fake()->createWithContent('karya.txt', 'private content'),
        ]);
        $portfolio = Portfolio::firstOrFail();
        $response->assertRedirect(route('siswa.portfolio.show', $portfolio));
        Storage::disk('local')->assertExists($portfolio->file_path);
        $this->actingAs($outsider)->get(route('siswa.portfolio.show', $portfolio))->assertNotFound();
        $this->actingAs($teacher)->get(route('pengajar.portfolio.student.show', $student))->assertOk()->assertDontSee('Karya privat');

        $this->actingAs($student)->put(route('siswa.portfolio.update', $portfolio), [
            'judul' => 'Karya publik', 'deskripsi' => 'Dibagikan secara sadar.', 'is_public' => 1, 'publish_consent' => 1,
        ])->assertRedirect();
        $this->actingAs($teacher)->get(route('pengajar.portfolio.students.index'))->assertOk()->assertSee($student->name)->assertDontSee($outsider->name);
        $this->actingAs($outsider)->get(route('siswa.portfolio.show', $portfolio))->assertOk()->assertSee('Karya publik');
        $this->actingAs($teacher)->get(route('pengajar.portfolio.student.show', $student))->assertOk()->assertSee('Karya publik');
        $this->actingAs($outsider)->get(route('portfolio.download', $portfolio))->assertOk();
        $this->actingAs($outsider)->delete(route('siswa.portfolio.destroy', $portfolio))->assertNotFound();
    }

    public function test_ipfs_upload_requires_public_consent_and_fake_adapter_sets_cid(): void
    {
        Storage::fake('local');
        $student = $this->user('siswa');
        $fake = new class implements IpfsAdapter {
            public function upload(string $path, string $filename, string $contentType): array { return ['cid' => 'bafytestcid', 'gateway_url' => 'https://gateway.test/bafytestcid']; }
        };
        $this->app->instance(IpfsAdapter::class, $fake);
        $this->actingAs($student)->post(route('siswa.portfolio.store'), [
            'judul' => 'Karya publik', 'is_public' => 1, 'publish_to_ipfs' => 1,
            'file' => UploadedFile::fake()->createWithContent('karya.txt', 'approved public content'),
        ])->assertSessionHasErrors('publish_consent');
        $this->post(route('siswa.portfolio.store'), [
            'judul' => 'Karya publik', 'is_public' => 1, 'publish_consent' => 1, 'publish_to_ipfs' => 1,
            'file' => UploadedFile::fake()->createWithContent('karya.txt', 'approved public content'),
        ])->assertRedirect();
        $this->assertDatabaseHas('portfolio', ['siswa_id' => $student->id, 'ipfs_cid' => 'bafytestcid', 'is_public' => 1]);
    }

    public function test_voting_enforces_enrollment_period_single_vote_and_hides_results_until_closed(): void
    {
        $teacher = $this->user('pengajar');
        $student = $this->user('siswa');
        $outsider = $this->user('siswa');
        $class = $this->classFor($teacher);
        $this->enroll($student, $class);
        $this->actingAs($teacher)->post(route('pengajar.voting.store'), [
            'kelas_id' => $class->id, 'judul' => 'Pilihan kelas', 'deskripsi' => 'Pilih topik.', 'jenis_voting' => 'poll',
            'starts_at' => now()->addMinute()->format('Y-m-d\TH:i'), 'ends_at' => now()->addHour()->format('Y-m-d\TH:i'),
            'options' => ['Topik A', 'Topik B'],
        ])->assertRedirect();
        $voting = VotingDao::firstOrFail();
        $this->actingAs($teacher)->post(route('pengajar.voting.publish', $voting))->assertRedirect();
        $this->actingAs($outsider)->get(route('siswa.voting.show', $voting))->assertNotFound();
        $voting->forceFill(['starts_at' => now()->subMinute()])->save();
        $option = $voting->options()->firstOrFail();
        $this->actingAs($student)->get(route('siswa.voting.show', $voting))->assertOk()->assertDontSee('Topik A</span>');
        $this->post(route('siswa.voting.vote', $voting), ['voting_option_id' => $option->id])->assertRedirect();
        $this->post(route('siswa.voting.vote', $voting), ['voting_option_id' => $option->id])->assertSessionHasErrors('voting');
        $this->assertDatabaseCount('voting_log', 1);
        $this->travel(2)->hours();
        $this->artisan('simpan:close-votings')->assertExitCode(0);
        $this->assertSame('closed', $voting->fresh()->status);
        $this->get(route('siswa.voting.show', $voting))->assertOk()->assertSee('1 suara');
    }

    public function test_marketplace_templates_publish_copy_and_admin_verification_do_not_mutate_source(): void
    {
        $teacher = $this->user('pengajar');
        $admin = $this->user('admin');
        $class = $this->classFor($teacher);
        $task = $this->taskFor($teacher, $class);
        $this->actingAs($teacher)->get(route('pengajar.marketplace.create'))->assertOk();
        $this->actingAs($teacher)->post(route('pengajar.marketplace.store'), [
            'nama' => 'Template analisis', 'deskripsi' => 'Rubrik umum.', 'kategori' => 'Laporan',
            'criteria' => [
                ['nama' => 'Analisis', 'deskripsi' => 'Kedalaman analisis', 'bobot' => 40, 'skor_maksimum' => 10],
                ['nama' => 'Bukti', 'deskripsi' => 'Kualitas bukti', 'bobot' => 60, 'skor_maksimum' => 10],
            ],
        ])->assertRedirect();
        $listing = RubrikMarketplace::firstOrFail();
        $this->actingAs($teacher)->get(route('marketplace.show', $listing))->assertOk()->assertSee('Ajukan untuk diterbitkan');
        $this->post(route('pengajar.marketplace.publish', $listing))->assertRedirect();
        $this->get(route('marketplace.show', $listing))->assertOk()->assertSee('Salin ke tugas');
        $this->get(route('marketplace.index'))->assertOk()->assertSee('Template analisis');
        $this->post(route('pengajar.marketplace.copy', $listing), ['tugas_id' => $task->id])->assertRedirect();
        $this->assertDatabaseCount('rubrik', 2);
        $this->actingAs($admin)->patch(route('admin.marketplace.verify', $listing))->assertRedirect();
        $this->assertTrue($listing->fresh()->is_verified);
        $this->assertSame('Template analisis', $listing->fresh()->nama);
    }

    public function test_reputation_ledger_is_signed_idempotent_and_user_scoped(): void
    {
        $user = $this->user('siswa');
        $other = $this->user('siswa');
        $ledger = app(ReputationLedgerService::class);
        $entry = $ledger->record($user, 'approved_activity', 5, 'activity:one', 'Poin aktivitas');
        $duplicate = $ledger->record($user, 'approved_activity', 50, 'activity:one', 'Duplicate');
        $ledger->record($user, 'manual_correction', -2, 'activity:two', 'Koreksi admin');
        $this->assertSame($entry->id, $duplicate->id);
        $this->assertSame(3, $ledger->balance($user));
        $this->assertDatabaseCount('token_reputasi', 2);
        $this->actingAs($other)->get(route('reputation.index'))->assertOk()->assertDontSee('Poin aktivitas');
        $this->actingAs($user)->get(route('reputation.index'))->assertOk()->assertSee('3');
    }

    public function test_blockchain_outbox_hash_is_idempotent_and_fake_adapter_confirms_receipt(): void
    {
        $teacher = $this->user('pengajar');
        $class = $this->classFor($teacher);
        $task = $this->taskFor($teacher, $class);
        Config::set('services.simpan_blockchain.rpc_url', 'https://rpc.example.test');
        Config::set('services.simpan_blockchain.contract_address', '0x'.str_repeat('1', 40));
        Config::set('services.simpan_blockchain.wallet_address', '0x'.str_repeat('2', 40));
        Config::set('services.simpan_blockchain.private_key', 'test-only');
        $fake = new class implements BlockchainAdapter {
            public function submit(\App\Models\BlockchainTransaction $transaction): array { return ['transaction_hash' => '0x'.str_repeat('a', 64), 'block_number' => null]; }
            public function receipt(string $transactionHash): array { return ['confirmed' => true, 'failed' => false, 'block_number' => 123]; }
        };
        $this->app->instance(BlockchainAdapter::class, $fake);
        $service = app(BlockchainTransactionService::class);
        $first = $service->record('task_published', $task, ['task_id' => $task->id, 'title' => 'private title'], 'task:'.$task->id.':published');
        $again = $service->record('task_published', $task, ['task_id' => $task->id, 'title' => 'different'], 'task:'.$task->id.':published');
        $this->assertSame($first->id, $again->id);
        $this->assertSame(64, strlen($first->payload_hash));
        $this->assertDatabaseMissing('blockchain_transaction', ['payload_hash' => 'private title']);
        (new SubmitBlockchainTransaction($first->id))->handle($fake);
        $this->assertSame('submitted', $first->fresh()->status);
        app(\App\Console\Commands\PollBlockchainTransactions::class)->handle($fake);
        $this->assertSame('confirmed', $first->fresh()->status);
        $this->assertSame(1, $first->fresh()->attempt_count);
    }

    public function test_blockchain_errors_do_not_persist_rpc_credentials_or_raw_messages(): void
    {
        $teacher = $this->user('pengajar');
        $task = $this->taskFor($teacher, $this->classFor($teacher));
        $transaction = app(BlockchainTransactionService::class)->record('task_published', $task, ['event' => 'published'], 'task:'.$task->id.':error-test');
        $transaction->forceFill(['status' => 'pending', 'failed_at' => null, 'error_message' => null])->save();
        $fake = new class implements BlockchainAdapter {
            public function submit(BlockchainTransaction $transaction): array { throw new \RuntimeException('RPC URL https://rpc.example.test/?token=private-secret'); }
            public function receipt(string $transactionHash): array { return ['confirmed' => false, 'block_number' => null]; }
        };

        try {
            (new SubmitBlockchainTransaction($transaction->id))->handle($fake);
            $this->fail('Pengiriman yang gagal harus dilempar sebagai kegagalan generik.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Pengiriman blockchain gagal; periksa konfigurasi dan log operasional.', $exception->getMessage());
        }

        $storedError = $transaction->fresh()->error_message;
        $this->assertSame('Pengiriman blockchain gagal; periksa konfigurasi dan log operasional.', $storedError);
        $this->assertStringNotContainsString('private-secret', $storedError);
    }

    public function test_diagnostics_require_per_run_consent_and_provider_results_are_indicative(): void
    {
        Storage::fake('local');
        $teacher = $this->user('pengajar');
        $student = $this->user('siswa');
        $outsider = $this->user('siswa');
        $class = $this->classFor($teacher);
        $task = $this->taskFor($teacher, $class);
        $this->enroll($student, $class);
        $this->enroll($outsider, $class);
        Storage::disk('local')->put('assignments/private.txt', 'document content');
        $submission = PengumpulanTugas::create(['tugas_id' => $task->id, 'siswa_id' => $student->id, 'attempt_no' => 1,
            'submitted_at' => now(), 'status' => 'submitted', 'stored_path' => 'assignments/private.txt', 'original_filename' => 'answer.txt', 'mime_type' => 'text/plain']);
        $ai = new class implements AiWritingDetector { public function inspect(string $path, string $filename, string $contentType): array { return ['score' => 82.5, 'result' => ['verdict' => 'mixed']]; } };
        $plagiarism = new class implements PlagiarismChecker { public function inspect(string $path, string $filename, string $contentType): array { throw new \RuntimeException('Provider down'); } };
        $this->app->instance(AiWritingDetector::class, $ai);
        $this->app->instance(PlagiarismChecker::class, $plagiarism);
        $this->actingAs($student)->post(route('siswa.diagnosis.run', $submission), [])->assertSessionHasErrors('consent_external');
        $this->assertDatabaseCount('self_diagnostic_log', 0);
        $this->post(route('siswa.diagnosis.run', $submission), ['consent_external' => 1])->assertRedirect();
        $log = SelfDiagnosticLog::firstOrFail();
        $this->assertSame('partial', $log->status);
        $this->assertSame('82.50', $log->ai_score);
        $this->assertSame('Provider plagiarisme tidak tersedia atau pemeriksaan gagal.', data_get($log->plagiarism_result, 'error'));
        $this->assertTrue((bool) data_get($log->ai_result, 'consented_at'));
        $this->actingAs($outsider)->get(route('siswa.diagnosis.index'))->assertOk();
        $this->actingAs($teacher)->get(route('pengajar.diagnosis.show', $log))->assertOk()->assertSee('bukan bukti pasti');
    }

    private function user(string $role): User
    {
        static $number = 0;
        $user = User::factory()->create(['email' => 'milestone4.'.(++$number).'@example.test']);
        $user->forceFill(['role' => $role])->save();
        return $user;
    }

    private function classFor(User $teacher): Kelas
    {
        static $number = 0;
        $faculty = Fakultas::create(['nama' => 'Fakultas M4 '.(++$number)]);
        $major = Jurusan::create(['fakultas_id' => $faculty->id, 'nama' => 'Jurusan M4 '.$number]);
        return Kelas::create(['jurusan_id' => $major->id, 'pengajar_id' => $teacher->id, 'nama' => 'Kelas M4 '.$number]);
    }

    private function taskFor(User $teacher, Kelas $class): Tugas
    {
        return Tugas::create(['kelas_id' => $class->id, 'pengajar_id' => $teacher->id, 'judul' => 'Tugas M4',
            'deadline_at' => now()->addDay(), 'status' => 'published', 'published_at' => now()]);
    }

    private function enroll(User $student, Kelas $class): void
    {
        KelasSiswa::create(['kelas_id' => $class->id, 'siswa_id' => $student->id]);
    }
}
