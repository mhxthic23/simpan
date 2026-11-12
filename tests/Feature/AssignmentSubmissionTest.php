<?php

namespace Tests\Feature;

use App\Models\Fakultas;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\PengumpulanTugas;
use App\Models\Tugas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssignmentSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_task_for_owned_class_and_cannot_manage_another_teachers_class(): void
    {
        $teacher = $this->makeUser('pengajar');
        $otherTeacher = $this->makeUser('pengajar');
        $class = $this->makeClass($teacher, 'Kelas Saya');
        $otherClass = $this->makeClass($otherTeacher, 'Kelas Lain');

        $this->actingAs($teacher)->post(route('pengajar.tugas.store'), $this->taskData($class, [
            'pengajar_id' => $otherTeacher->id,
        ]))->assertRedirect();

        $task = Tugas::where('judul', 'Tugas Uji')->firstOrFail();
        $this->assertSame($teacher->id, $task->pengajar_id);
        $this->assertSame($class->id, $task->kelas_id);
        $this->assertSame('published', $task->status);
        $this->assertNotNull($task->published_at);
        $this->assertSame(2 * 1024 * 1024, $task->max_file_size);
        $this->assertSame(['pdf', 'docx'], $task->allowed_file_types);
        $this->actingAs($teacher)->get(route('pengajar.tugas.index'))->assertOk()->assertSee('Tugas Uji');
        $this->actingAs($teacher)->get(route('pengajar.tugas.show', $task))->assertOk();
        $this->actingAs($teacher)->get(route('pengajar.tugas.edit', $task))->assertOk();

        $this->actingAs($teacher)->post(route('pengajar.tugas.store'), $this->taskData($otherClass))
            ->assertSessionHasErrors('kelas_id');
        $foreignTask = $this->makeTask($otherTeacher, $otherClass, 'Tugas Milik Pengajar Lain');
        $this->actingAs($teacher)->put(route('pengajar.tugas.update', $foreignTask), $this->taskData($class))
            ->assertNotFound();
        $this->assertDatabaseMissing('tugas', ['kelas_id' => $otherClass->id, 'judul' => 'Tugas Uji']);
    }

    public function test_student_only_sees_and_opens_published_tasks_from_enrolled_classes(): void
    {
        $teacher = $this->makeUser('pengajar');
        $student = $this->makeUser('siswa');
        $classA = $this->makeClass($teacher, 'Kelas Diikuti');
        $classB = $this->makeClass($teacher, 'Kelas Tidak Diikuti');
        $taskA = $this->makeTask($teacher, $classA, 'Tugas Terlihat');
        $taskB = $this->makeTask($teacher, $classB, 'Tugas Rahasia');
        $this->enroll($student, $classA);

        $this->actingAs($student)->get(route('siswa.tugas.index'))
            ->assertOk()->assertSee('Tugas Terlihat')->assertDontSee('Tugas Rahasia');
        $this->actingAs($student)->get(route('siswa.tugas.show', $taskA))->assertOk();
        $this->actingAs($student)->get(route('siswa.tugas.show', $taskB))->assertNotFound();
    }

    public function test_student_can_submit_and_resubmit_before_deadline_with_private_attempt_files(): void
    {
        Storage::fake('local');
        $teacher = $this->makeUser('pengajar');
        $student = $this->makeUser('siswa');
        $class = $this->makeClass($teacher);
        $task = $this->makeTask($teacher, $class, 'Tugas Kirim', ['pdf'], 2 * 1024 * 1024);
        $this->enroll($student, $class);

        $firstResponse = $this->actingAs($student)->post(route('siswa.tugas.pengumpulan.store', $task), [
            'file' => $this->pdfFile('jawaban.pdf'),
        ]);
        $firstResponse->assertRedirect(route('siswa.tugas.show', $task));
        $first = PengumpulanTugas::where('tugas_id', $task->id)->where('siswa_id', $student->id)->firstOrFail();
        $this->assertSame(1, $first->attempt_no);
        $this->assertSame('submitted', $first->status);
        $this->assertNotNull($first->submitted_at);
        $this->assertSame('jawaban.pdf', $first->original_filename);
        $this->assertSame('application/pdf', $first->mime_type);
        $this->assertGreaterThan(0, $first->file_size);
        $this->assertStringStartsWith('assignments/'.$task->id.'/submissions/'.$student->id.'/', $first->stored_path);
        Storage::disk('local')->assertExists($first->stored_path);
        $this->assertStringNotContainsString('public', $first->stored_path);

        $secondResponse = $this->post(route('siswa.tugas.pengumpulan.store', $task), [
            'file' => $this->pdfFile('jawaban-revisi.pdf'),
        ]);
        $secondResponse->assertRedirect(route('siswa.tugas.show', $task));
        $second = PengumpulanTugas::where('tugas_id', $task->id)->where('siswa_id', $student->id)->orderByDesc('attempt_no')->firstOrFail();
        $this->assertSame(2, $second->attempt_no);
        Storage::disk('local')->assertExists($first->stored_path);
        Storage::disk('local')->assertExists($second->stored_path);

        $this->get(route('siswa.pengumpulan.download', $first))->assertOk();
        $otherStudent = $this->makeUser('siswa');
        $this->actingAs($otherStudent)->get(route('siswa.pengumpulan.download', $first))->assertNotFound();

        $this->actingAs($student)->get(route('siswa.tugas.show', $task))
            ->assertOk()->assertSee('Riwayat pengumpulan')->assertSee('jawaban-revisi.pdf');
    }

    public function test_student_cannot_submit_for_a_class_they_do_not_attend(): void
    {
        Storage::fake('local');
        $teacher = $this->makeUser('pengajar');
        $student = $this->makeUser('siswa');
        $class = $this->makeClass($teacher);
        $task = $this->makeTask($teacher, $class);

        $this->actingAs($student)->post(route('siswa.tugas.pengumpulan.store', $task), [
            'file' => $this->pdfFile(),
        ])->assertNotFound();

        $this->assertDatabaseMissing('pengumpulan_tugas', ['tugas_id' => $task->id, 'siswa_id' => $student->id]);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_submission_after_deadline_is_rejected_without_storing_a_file(): void
    {
        Storage::fake('local');
        $teacher = $this->makeUser('pengajar');
        $student = $this->makeUser('siswa');
        $class = $this->makeClass($teacher);
        $task = $this->makeTask($teacher, $class, 'Tugas Lewat Tenggat', ['pdf'], null, now()->subMinute());
        $this->enroll($student, $class);

        $this->actingAs($student)->post(route('siswa.tugas.pengumpulan.store', $task), [
            'file' => $this->pdfFile(),
        ])->assertSessionHasErrors('file');

        $this->assertDatabaseMissing('pengumpulan_tugas', ['tugas_id' => $task->id, 'siswa_id' => $student->id]);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_upload_rejects_disallowed_extension_and_oversize_file(): void
    {
        Storage::fake('local');
        $teacher = $this->makeUser('pengajar');
        $student = $this->makeUser('siswa');
        $class = $this->makeClass($teacher);
        $task = $this->makeTask($teacher, $class, 'Tugas File', ['pdf'], 1 * 1024 * 1024);
        $this->enroll($student, $class);

        $this->actingAs($student)->post(route('siswa.tugas.pengumpulan.store', $task), [
            'file' => UploadedFile::fake()->create('script.exe', 10, 'application/x-msdownload'),
        ])->assertSessionHasErrors('file');

        $this->post(route('siswa.tugas.pengumpulan.store', $task), [
            'file' => UploadedFile::fake()->create('terlalu-besar.pdf', 1025, 'application/pdf'),
        ])->assertSessionHasErrors('file');

        $this->assertDatabaseCount('pengumpulan_tugas', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_uploaded_file_is_removed_if_submission_record_cannot_be_written(): void
    {
        Storage::fake('local');
        $teacher = $this->makeUser('pengajar');
        $student = $this->makeUser('siswa');
        $class = $this->makeClass($teacher);
        $task = $this->makeTask($teacher, $class);
        $this->enroll($student, $class);

        PengumpulanTugas::creating(function (): void {
            throw new \RuntimeException('Simulated database write failure.');
        });

        $this->actingAs($student)->post(route('siswa.tugas.pengumpulan.store', $task), [
            'file' => $this->pdfFile(),
        ])->assertServerError();

        $this->assertDatabaseCount('pengumpulan_tugas', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_teacher_can_view_and_download_only_submissions_for_owned_task(): void
    {
        Storage::fake('local');
        $teacherA = $this->makeUser('pengajar');
        $teacherB = $this->makeUser('pengajar');
        $student = $this->makeUser('siswa');
        $classA = $this->makeClass($teacherA, 'Kelas A');
        $classB = $this->makeClass($teacherB, 'Kelas B');
        $taskA = $this->makeTask($teacherA, $classA, 'Tugas A');
        $taskB = $this->makeTask($teacherB, $classB, 'Tugas B');
        $this->enroll($student, $classA);
        $this->enroll($student, $classB);

        $this->actingAs($student)->post(route('siswa.tugas.pengumpulan.store', $taskA), ['file' => $this->pdfFile()])->assertRedirect();
        $submission = PengumpulanTugas::where('tugas_id', $taskA->id)->firstOrFail();

        $this->actingAs($teacherA)->get(route('pengajar.tugas.pengumpulan.index', $taskA))
            ->assertOk()->assertSee($student->name);
        $this->actingAs($teacherA)->get(route('pengajar.tugas.missing', $taskA))->assertOk();
        $this->actingAs($teacherA)->get(route('pengajar.tugas.pengumpulan.show', [$taskA, $submission]))->assertOk();
        $this->actingAs($teacherA)->get(route('pengajar.pengumpulan.download', $submission))->assertOk();

        $this->actingAs($teacherB)->get(route('pengajar.tugas.pengumpulan.index', $taskA))->assertNotFound();
        $this->actingAs($teacherB)->get(route('pengajar.tugas.pengumpulan.show', [$taskB, $submission]))->assertNotFound();
        $this->actingAs($teacherB)->get(route('pengajar.pengumpulan.download', $submission))->assertNotFound();
    }

    public function test_task_with_submissions_cannot_be_deleted_and_other_roles_are_denied(): void
    {
        $this->get(route('siswa.tugas.index'))->assertRedirect(route('login'));

        $teacher = $this->makeUser('pengajar');
        $student = $this->makeUser('siswa');
        $admin = $this->makeUser('admin');
        $class = $this->makeClass($teacher);
        $task = $this->makeTask($teacher, $class);
        $this->enroll($student, $class);
        PengumpulanTugas::create([
            'tugas_id' => $task->id,
            'siswa_id' => $student->id,
            'attempt_no' => 1,
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);

        $this->actingAs($teacher)->delete(route('pengajar.tugas.destroy', $task))->assertRedirect();
        $this->assertDatabaseHas('tugas', ['id' => $task->id]);

        $emptyTask = $this->makeTask($teacher, $class, 'Tugas Tanpa Pengumpulan');
        $this->actingAs($teacher)->delete(route('pengajar.tugas.destroy', $emptyTask))
            ->assertRedirect(route('pengajar.tugas.index'));
        $this->assertDatabaseMissing('tugas', ['id' => $emptyTask->id]);

        $this->actingAs($student)->get(route('pengajar.tugas.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('pengajar.tugas.index'))->assertForbidden();
        $this->actingAs($teacher)->get(route('siswa.tugas.index'))->assertForbidden();
    }

    private function makeUser(string $role): User
    {
        static $number = 0;
        $number++;
        $user = User::factory()->create(['email' => "assignment.{$number}@example.test"]);
        $user->forceFill(['role' => $role])->save();

        return $user;
    }

    private function makeClass(User $teacher, string $name = 'Kelas Assignment'): Kelas
    {
        static $number = 0;
        $number++;
        $faculty = Fakultas::create(['nama' => "Fakultas {$number}"]);
        $major = Jurusan::create(['fakultas_id' => $faculty->id, 'nama' => "Jurusan {$number}"]);

        return Kelas::create(['jurusan_id' => $major->id, 'pengajar_id' => $teacher->id, 'nama' => $name]);
    }

    /** @param array<int, string> $extensions */
    private function makeTask(User $teacher, Kelas $class, string $title = 'Tugas Uji', array $extensions = ['pdf'], ?int $maxBytes = null, mixed $deadline = null): Tugas
    {
        return Tugas::create([
            'kelas_id' => $class->id,
            'pengajar_id' => $teacher->id,
            'judul' => $title,
            'deskripsi' => 'Instruksi pengumpulan uji.',
            'deadline_at' => $deadline ?? now()->addDay(),
            'status' => 'published',
            'published_at' => now(),
            'max_file_size' => $maxBytes,
            'allowed_file_types' => $extensions,
        ]);
    }

    private function enroll(User $student, Kelas $class): void
    {
        KelasSiswa::create(['kelas_id' => $class->id, 'siswa_id' => $student->id]);
    }

    private function taskData(Kelas $class, array $overrides = []): array
    {
        return array_merge([
            'kelas_id' => $class->id,
            'judul' => 'Tugas Uji',
            'deskripsi' => 'Instruksi tugas yang aman.',
            'deadline_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'status' => 'published',
            'max_file_size_mb' => 2,
            'allowed_file_types' => ['pdf', 'docx'],
        ], $overrides);
    }

    private function pdfFile(string $name = 'jawaban.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\nSIMPAN test content\n%%EOF");
    }
}
