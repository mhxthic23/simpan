<?php

namespace Tests\Feature;

use App\Models\Disputasi;
use App\Models\Fakultas;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\PengumpulanTugas;
use App\Models\Penilaian;
use App\Models\Rubrik;
use App\Models\Tugas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MilestoneFiveWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_student_and_admin_complete_academic_workflow(): void
    {
        $this->assertSame('sqlite', config('database.default'), 'Feature tests must never connect to the project database.');
        Storage::fake('local');
        $teacher = $this->user('pengajar');
        $student = $this->user('siswa');
        $admin = $this->user('admin');
        $faculty = Fakultas::create(['nama' => 'Fakultas Workflow']);
        $major = Jurusan::create(['fakultas_id' => $faculty->id, 'nama' => 'Jurusan Workflow']);
        $class = Kelas::create(['jurusan_id' => $major->id, 'pengajar_id' => $teacher->id, 'nama' => 'Kelas Workflow']);
        KelasSiswa::create(['kelas_id' => $class->id, 'siswa_id' => $student->id]);

        $this->actingAs($teacher)->post(route('pengajar.tugas.store'), [
            'kelas_id' => $class->id, 'judul' => 'Tugas Workflow', 'deskripsi' => 'Alur ujung ke ujung.',
            'deadline_at' => now()->addDay()->format('Y-m-d\\TH:i'), 'status' => 'published',
            'max_file_size_mb' => 2, 'allowed_file_types' => ['pdf'],
        ])->assertRedirect();
        $task = Tugas::where('judul', 'Tugas Workflow')->firstOrFail();

        $this->actingAs($student)->get(route('siswa.tugas.show', $task))->assertOk();
        $this->post(route('siswa.tugas.pengumpulan.store', $task), [
            'file' => UploadedFile::fake()->createWithContent('jawaban.pdf', "%PDF-1.4\\nworkflow\\n%%EOF"),
        ])->assertRedirect();
        $submission = PengumpulanTugas::where('tugas_id', $task->id)->firstOrFail();
        Storage::disk('local')->assertExists($submission->stored_path);
        $this->get('/storage/'.$submission->stored_path)->assertNotFound();

        $this->actingAs($teacher)->post(route('pengajar.tugas.rubrik.store', $task), ['criteria' => [
            ['nama' => 'Analisis', 'deskripsi' => 'Ketepatan analisis', 'bobot' => 40, 'skor_maksimum' => 10],
            ['nama' => 'Bukti', 'deskripsi' => 'Dukungan bukti', 'bobot' => 60, 'skor_maksimum' => 10],
        ]])->assertRedirect();
        $criteria = Rubrik::where('tugas_id', $task->id)->orderBy('urutan')->get();
        $this->post(route('pengajar.penilaian.save', [$task, $submission]), [
            'status' => 'published', 'scores' => [$criteria[0]->id => 8, $criteria[1]->id => 7], 'feedback' => 'Umpan balik awal.',
        ])->assertRedirect();
        $assessment = Penilaian::where('pengumpulan_tugas_id', $submission->id)->firstOrFail();

        $this->actingAs($student)->get(route('siswa.nilai.show', $assessment))->assertOk()->assertSee('Umpan balik awal.');
        $this->post(route('siswa.nilai.disputasi.store', $assessment), ['alasan' => 'Mohon tinjau kembali skor bukti.'])->assertRedirect();
        $dispute = Disputasi::where('penilaian_id', $assessment->id)->firstOrFail();
        $this->actingAs($teacher)->post(route('pengajar.disputasi.review', $dispute), [
            'tanggapan_pengajar' => 'Saya sedang meninjau penilaian.',
        ])->assertRedirect();
        $this->post(route('pengajar.disputasi.resolve', $dispute), [
            'keputusan_tipe' => 'accepted', 'keputusan' => 'Skor bukti disesuaikan setelah pemeriksaan.',
            'scores' => [$criteria[0]->id => 8, $criteria[1]->id => 8],
        ])->assertRedirect();

        $this->assertSame('accepted', $dispute->fresh()->status);
        $this->assertSame('80.00', $assessment->fresh()->nilai_total);
        $this->assertDatabaseCount('penilaian_riwayat', 2);
        $this->assertDatabaseCount('disputasi_riwayat', 3);
        $this->actingAs($student)->get(route('siswa.nilai.show', $assessment))->assertOk()->assertSee('Skor bukti disesuaikan');
        $this->actingAs($admin)->get(route('admin.penilaian.riwayat.show', $assessment))->assertOk()->assertSee($student->name);
    }

    private function user(string $role): User
    {
        static $number = 0;
        $user = User::factory()->create(['email' => 'milestone5.'.(++$number).'@example.test']);
        $user->forceFill(['role' => $role])->save();

        return $user;
    }
}
