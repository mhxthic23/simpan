<?php

namespace Tests\Feature;

use App\Models\Disputasi;
use App\Models\Fakultas;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\PengumpulanTugas;
use App\Models\Penilaian;
use App\Models\PenilaianDetail;
use App\Models\PenilaianRiwayat;
use App\Models\Rubrik;
use App\Models\Tugas;
use App\Models\User;
use App\Services\PenilaianService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentDisputeTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_manage_rubric_and_rubric_is_locked_after_use(): void
    {
        [$teacher, $class, $task, $student, $submission] = $this->academicFixture();
        $otherTeacher = $this->makeUser('pengajar');

        $this->actingAs($teacher)->get(route('pengajar.tugas.rubrik.index', $task))->assertOk();
        $this->post(route('pengajar.tugas.rubrik.store', $task), $this->criteriaPayload())
            ->assertRedirect(route('pengajar.tugas.rubrik.index', $task));
        $criteria = $task->rubrik()->orderBy('urutan')->get();
        $this->assertCount(2, $criteria);
        $this->assertSame(10000, $criteria->sum(fn (Rubrik $item) => (int) round((float) $item->bobot * 100)));

        $this->put(route('pengajar.tugas.rubrik.update', $task), ['criteria' => [
            ['id' => $criteria[0]->id, 'nama' => 'Revisi', 'deskripsi' => 'Indikator baru', 'bobot' => 50, 'skor_maksimum' => 10],
            ['id' => $criteria[1]->id, 'nama' => 'Kedua', 'deskripsi' => null, 'bobot' => 50, 'skor_maksimum' => 10],
        ]])->assertRedirect();
        $unusedTask = $this->makeTask($teacher, $class);
        $this->post(route('pengajar.tugas.rubrik.store', $unusedTask), $this->criteriaPayload())->assertRedirect();
        $unusedCriterion = $unusedTask->rubrik()->firstOrFail();
        $this->delete(route('pengajar.tugas.rubrik.destroy', [$unusedTask, $unusedCriterion]))
            ->assertRedirect();
        $this->assertDatabaseMissing('rubrik', ['id' => $unusedCriterion->id]);

        $this->put(route('pengajar.tugas.rubrik.update', $task), ['criteria' => [
            ['nama' => 'A', 'bobot' => 60, 'skor_maksimum' => 10],
            ['nama' => 'B', 'bobot' => 50, 'skor_maksimum' => 10],
        ]])->assertSessionHasErrors('criteria');

        $foreignTask = $this->makeTask($otherTeacher, $this->makeClass($otherTeacher));
        $this->get(route('pengajar.tugas.rubrik.index', $foreignTask))->assertNotFound();

        app(PenilaianService::class)->save($submission, $teacher, [$criteria[0]->id => 5, $criteria[1]->id => 5], [], null, 'draft');
        $this->put(route('pengajar.tugas.rubrik.update', $task), ['criteria' => [
            ['id' => $criteria[0]->id, 'nama' => 'Tidak boleh', 'bobot' => 50, 'skor_maksimum' => 10],
            ['id' => $criteria[1]->id, 'nama' => 'Kedua', 'bobot' => 50, 'skor_maksimum' => 10],
        ]])->assertSessionHasErrors('criteria');
        $this->delete(route('pengajar.tugas.rubrik.destroy', [$task, $criteria[0]]))->assertSessionHas('error');
        $this->assertDatabaseHas('rubrik', ['id' => $criteria[0]->id, 'nama' => 'Revisi']);
    }

    public function test_grade_is_recomputed_published_and_history_is_appended(): void
    {
        [$teacher, $class, $task, $student, $submission] = $this->academicFixture();
        $this->createCriteria($task);
        $criteria = $task->rubrik()->orderBy('urutan')->get();

        $this->actingAs($teacher)->post(route('pengajar.penilaian.save', [$task, $submission]), [
            'status' => 'published', 'scores' => [$criteria[0]->id => 40.01, $criteria[1]->id => 30],
        ])->assertSessionHasErrors("scores.{$criteria[0]->id}");

        $this->actingAs($teacher)->post(route('pengajar.penilaian.save', [$task, $submission]), [
            'status' => 'published',
            'nilai_total' => 100,
            'scores' => [$criteria[0]->id => 25, $criteria[1]->id => 30],
            'feedback' => 'Umpan balik umum',
        ])->assertRedirect();

        $assessment = Penilaian::firstOrFail();
        $this->assertSame('61.00', $assessment->nilai_total);
        $this->assertSame('published', $assessment->status);
        $this->assertNotNull($assessment->published_at);
        $this->assertDatabaseCount('penilaian_riwayat', 1);
        $this->assertSame(2, $assessment->detail()->count());

        $this->put(route('pengajar.penilaian.save', [$task, $submission]), [
            'status' => 'draft', 'scores' => [$criteria[0]->id => 20, $criteria[1]->id => 20],
        ])->assertStatus(405);
        $this->post(route('pengajar.penilaian.save', [$task, $submission]), [
            'status' => 'published', 'scores' => [$criteria[0]->id => 20, $criteria[1]->id => 20],
        ])->assertSessionHasErrors('alasan_perubahan');
        $this->post(route('pengajar.penilaian.save', [$task, $submission]), [
            'status' => 'published', 'scores' => [$criteria[0]->id => 20, $criteria[1]->id => 20],
            'alasan_perubahan' => 'Koreksi pemeriksaan', 'detail_feedback' => [],
        ])->assertRedirect();

        $assessment->refresh();
        $this->assertSame('44.00', $assessment->nilai_total);
        $this->assertDatabaseCount('penilaian_riwayat', 2);
        $this->assertSame('61.00', $assessment->riwayat()->latest('id')->first()->nilai_sebelumnya);

        $this->actingAs($student)->get(route('siswa.nilai.index'))->assertOk()->assertSee('44.00');
        $this->get(route('siswa.nilai.show', $assessment))->assertOk()->assertSee('Riwayat nilai yang dipublikasikan');
    }

    public function test_draft_grade_is_hidden_and_students_cannot_read_other_students_assessment(): void
    {
        [$teacher, $class, $task, $student, $submission] = $this->academicFixture();
        $this->createCriteria($task);
        $criteria = $task->rubrik()->orderBy('urutan')->get();
        $assessment = app(PenilaianService::class)->save($submission, $teacher,
            [$criteria[0]->id => 10, $criteria[1]->id => 10], [], null, 'draft');
        $otherStudent = $this->makeUser('siswa');
        $this->enroll($otherStudent, $class);

        $this->actingAs($student)->get(route('siswa.nilai.index'))->assertOk()->assertDontSee($task->judul);
        $this->get(route('siswa.nilai.show', $assessment))->assertNotFound();
        $this->actingAs($otherStudent)->get(route('siswa.nilai.show', $assessment))->assertNotFound();
        $this->actingAs($student)->post(route('siswa.nilai.disputasi.store', $assessment), ['alasan' => 'Mohon periksa kembali nilai saya.'])->assertNotFound();
    }

    public function test_student_can_dispute_own_published_grade_and_teacher_can_resolve_with_grade_history(): void
    {
        [$teacher, $class, $task, $student, $submission] = $this->academicFixture();
        $this->createCriteria($task);
        $criteria = $task->rubrik()->orderBy('urutan')->get();
        $assessment = app(PenilaianService::class)->save($submission, $teacher,
            [$criteria[0]->id => 20, $criteria[1]->id => 20], [$criteria[0]->id => 'Catatan tetap'], 'Awal', 'published');
        $otherStudent = $this->makeUser('siswa');
        $this->enroll($otherStudent, $class);

        $this->actingAs($otherStudent)->post(route('siswa.nilai.disputasi.store', $assessment), ['alasan' => 'Saya tidak memiliki nilai ini.'])->assertNotFound();
        $this->actingAs($student)->post(route('siswa.nilai.disputasi.store', $assessment), ['alasan' => 'Mohon tinjau ulang kriteria pertama.'])
            ->assertRedirect(route('siswa.nilai.show', $assessment));
        $dispute = Disputasi::firstOrFail();
        $this->assertSame('pending', $dispute->status);
        $this->assertDatabaseCount('disputasi_riwayat', 1);
        $this->post(route('siswa.nilai.disputasi.store', $assessment), ['alasan' => 'Pengajuan duplikat yang tidak seharusnya diterima.'])
            ->assertSessionHasErrors('alasan');

        $this->actingAs($teacher)->get(route('pengajar.disputasi.show', $dispute))->assertOk();
        $this->post(route('pengajar.disputasi.review', $dispute), ['tanggapan_pengajar' => 'Sedang memeriksa bukti dan rincian.'])->assertRedirect();
        $this->assertSame('reviewing', $dispute->fresh()->status);

        $this->post(route('pengajar.disputasi.resolve', $dispute), [
            'keputusan_tipe' => 'accepted', 'keputusan' => 'Skor kriteria pertama perlu dikoreksi.',
            'scores' => [$criteria[0]->id => 30, $criteria[1]->id => 20],
        ])->assertRedirect();

        $this->assertSame('accepted', $dispute->fresh()->status);
        $this->assertNotNull($dispute->fresh()->resolved_at);
        $this->assertSame('54.00', $assessment->fresh()->nilai_total);
        $this->assertSame('Catatan tetap', $assessment->fresh()->detail()->where('rubrik_id', $criteria[0]->id)->value('feedback'));
        $this->assertDatabaseCount('penilaian_riwayat', 2);
        $this->assertDatabaseCount('disputasi_riwayat', 3);
        $this->actingAs($student)->get(route('siswa.nilai.show', $assessment))->assertOk()->assertSee('Sedang memeriksa bukti');
    }

    public function test_teacher_scope_and_role_middleware_protect_grading_and_disputes(): void
    {
        [$teacher, $class, $task, $student, $submission] = $this->academicFixture();
        $otherTeacher = $this->makeUser('pengajar');
        $admin = $this->makeUser('admin');
        $this->actingAs($otherTeacher)->get(route('pengajar.penilaian.edit', [$task, $submission]))->assertNotFound();
        $this->actingAs($student)->get(route('pengajar.penilaian.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('pengajar.disputasi.index'))->assertForbidden();
        $this->actingAs($teacher)->get(route('admin.penilaian.riwayat.index'))->assertForbidden();

        $this->createCriteria($task);
        $criteria = $task->rubrik()->orderBy('urutan')->get();
        $assessment = app(PenilaianService::class)->save($submission, $teacher,
            [$criteria[0]->id => 20, $criteria[1]->id => 20], [], 'Audit', 'published');
        $this->actingAs($student)->post(route('siswa.nilai.disputasi.store', $assessment), ['alasan' => 'Mohon tinjau hasil penilaian ini.'])->assertRedirect();
        $dispute = Disputasi::firstOrFail();
        $this->actingAs($otherTeacher)->get(route('pengajar.disputasi.show', $dispute))->assertNotFound();
        $this->actingAs($admin)->get(route('admin.penilaian.riwayat.index'))->assertOk()->assertSee($student->name);
        $this->get(route('admin.penilaian.riwayat.show', $assessment))->assertOk()->assertSee('Snapshot');
    }

    public function test_teacher_can_reject_dispute_without_changing_published_grade(): void
    {
        [$teacher, $class, $task, $student, $submission] = $this->academicFixture();
        $this->createCriteria($task);
        $criteria = $task->rubrik()->orderBy('urutan')->get();
        $assessment = app(PenilaianService::class)->save($submission, $teacher,
            [$criteria[0]->id => 20, $criteria[1]->id => 20], [], null, 'published');
        $this->actingAs($student)->post(route('siswa.nilai.disputasi.store', $assessment), ['alasan' => 'Saya ingin mengajukan peninjauan nilai.'])->assertRedirect();
        $dispute = Disputasi::firstOrFail();

        $this->actingAs($teacher)->post(route('pengajar.disputasi.resolve', $dispute), [
            'keputusan_tipe' => 'rejected', 'keputusan' => 'Skor sudah sesuai dengan rubrik yang berlaku.',
        ])->assertRedirect();

        $this->assertSame('rejected', $dispute->fresh()->status);
        $this->assertSame('44.00', $assessment->fresh()->nilai_total);
        $this->assertDatabaseCount('penilaian_riwayat', 1);
        $this->assertDatabaseCount('disputasi_riwayat', 2);
    }

    public function test_failed_grade_transaction_leaves_no_partial_rows(): void
    {
        [$teacher, $class, $task, $student, $submission] = $this->academicFixture();
        $this->createCriteria($task);
        $criteria = $task->rubrik()->orderBy('urutan')->get();
        PenilaianDetail::creating(function (): void {
            throw new \RuntimeException('Simulated detail write failure.');
        });

        try {
            app(PenilaianService::class)->save($submission, $teacher,
                [$criteria[0]->id => 20, $criteria[1]->id => 20], [], null, 'published');
            $this->fail('Expected grading transaction to fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated detail write failure.', $exception->getMessage());
        }

        $this->assertDatabaseCount('penilaian', 0);
        $this->assertDatabaseCount('penilaian_detail', 0);
        $this->assertDatabaseCount('penilaian_riwayat', 0);
    }

    /** @return array{User, Kelas, Tugas, User, PengumpulanTugas} */
    private function academicFixture(): array
    {
        $teacher = $this->makeUser('pengajar');
        $student = $this->makeUser('siswa');
        $class = $this->makeClass($teacher);
        $task = $this->makeTask($teacher, $class);
        $this->enroll($student, $class);
        $submission = PengumpulanTugas::create([
            'tugas_id' => $task->id, 'siswa_id' => $student->id, 'attempt_no' => 1,
            'submitted_at' => now(), 'status' => 'submitted',
        ]);

        return [$teacher, $class, $task, $student, $submission];
    }

    private function makeUser(string $role): User
    {
        static $number = 0;
        $user = User::factory()->create(['email' => 'assessment.'.(++$number).'@example.test']);
        $user->forceFill(['role' => $role])->save();

        return $user;
    }

    private function makeClass(User $teacher): Kelas
    {
        static $number = 0;
        $faculty = Fakultas::create(['nama' => 'Fakultas Assessment '.(++$number)]);
        $major = Jurusan::create(['fakultas_id' => $faculty->id, 'nama' => 'Jurusan '.$number]);

        return Kelas::create(['jurusan_id' => $major->id, 'pengajar_id' => $teacher->id, 'nama' => 'Kelas '.$number]);
    }

    private function makeTask(User $teacher, Kelas $class): Tugas
    {
        return Tugas::create([
            'kelas_id' => $class->id, 'pengajar_id' => $teacher->id, 'judul' => 'Tugas Assessment',
            'deadline_at' => now()->addDay(), 'status' => 'published', 'published_at' => now(),
        ]);
    }

    private function enroll(User $student, Kelas $class): void
    {
        KelasSiswa::create(['kelas_id' => $class->id, 'siswa_id' => $student->id]);
    }

    /** @return array<string, mixed> */
    private function criteriaPayload(): array
    {
        return ['criteria' => [
            ['nama' => 'Analisis', 'deskripsi' => 'Ketepatan analisis', 'bobot' => 40, 'skor_maksimum' => 40],
            ['nama' => 'Penyajian', 'deskripsi' => 'Kejelasan penyajian', 'bobot' => 60, 'skor_maksimum' => 50],
        ]];
    }

    private function createCriteria(Tugas $task): void
    {
        foreach ($this->criteriaPayload()['criteria'] as $index => $item) {
            Rubrik::create(['tugas_id' => $task->id, ...$item, 'urutan' => $index + 1]);
        }
    }
}
