<?php

namespace Tests\Feature;

use App\Models\Fakultas;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\Tugas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAcademicManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_edit_search_and_delete_faculties(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get(route('admin.fakultas.create'))->assertOk();
        $this->actingAs($admin)->post(route('admin.fakultas.store'), ['nama' => 'Fakultas Sains'])
            ->assertRedirect(route('admin.fakultas.index'));
        $faculty = Fakultas::where('nama', 'Fakultas Sains')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.fakultas.index', ['q' => 'Sains']))
            ->assertOk()->assertSee('Fakultas Sains')->assertSee('Jumlah jurusan');
        $this->actingAs($admin)->get(route('admin.fakultas.edit', $faculty))->assertOk();
        $this->actingAs($admin)->put(route('admin.fakultas.update', $faculty), ['nama' => 'Fakultas Ilmu'])
            ->assertRedirect(route('admin.fakultas.index'));
        $this->assertDatabaseHas('fakultas', ['id' => $faculty->id, 'nama' => 'Fakultas Ilmu']);

        $this->actingAs($admin)->delete(route('admin.fakultas.destroy', $faculty))
            ->assertRedirect(route('admin.fakultas.index'));
        $this->assertDatabaseMissing('fakultas', ['id' => $faculty->id]);
    }

    public function test_fakultas_with_jurusan_cannot_be_deleted(): void
    {
        $admin = $this->makeUser('admin');
        $faculty = Fakultas::create(['nama' => 'Fakultas Terpakai']);
        Jurusan::create(['fakultas_id' => $faculty->id, 'nama' => 'Jurusan Terpakai']);

        $this->actingAs($admin)->delete(route('admin.fakultas.destroy', $faculty))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('fakultas', ['id' => $faculty->id]);
    }

    public function test_admin_can_create_edit_filter_and_delete_jurusan(): void
    {
        $admin = $this->makeUser('admin');
        $facultyA = Fakultas::create(['nama' => 'Fakultas A']);
        $facultyB = Fakultas::create(['nama' => 'Fakultas B']);

        $this->actingAs($admin)->get(route('admin.jurusan.create'))->assertOk();
        $this->actingAs($admin)->post(route('admin.jurusan.store'), [
            'fakultas_id' => $facultyA->id,
            'nama' => 'Teknik Uji',
        ])->assertRedirect(route('admin.jurusan.index'));
        $major = Jurusan::where('nama', 'Teknik Uji')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.jurusan.index', ['fakultas_id' => $facultyA->id]))
            ->assertOk()->assertSee('Teknik Uji')->assertSee('Fakultas A');
        $this->actingAs($admin)->get(route('admin.jurusan.edit', $major))->assertOk();
        $this->actingAs($admin)->put(route('admin.jurusan.update', $major), [
            'fakultas_id' => $facultyB->id,
            'nama' => 'Teknik Baru',
        ])->assertRedirect(route('admin.jurusan.index'));
        $this->assertDatabaseHas('jurusan', ['id' => $major->id, 'fakultas_id' => $facultyB->id, 'nama' => 'Teknik Baru']);

        $this->actingAs($admin)->delete(route('admin.jurusan.destroy', $major))
            ->assertRedirect(route('admin.jurusan.index'));
        $this->assertDatabaseMissing('jurusan', ['id' => $major->id]);
    }

    public function test_jurusan_with_class_cannot_be_deleted(): void
    {
        $admin = $this->makeUser('admin');
        [$faculty, $major, $teacher] = $this->academicContext();
        $class = Kelas::create(['jurusan_id' => $major->id, 'pengajar_id' => $teacher->id, 'nama' => 'Kelas A']);

        $this->actingAs($admin)->delete(route('admin.jurusan.destroy', $major))->assertSessionHas('error');
        $this->assertDatabaseHas('jurusan', ['id' => $major->id]);
    }

    public function test_admin_can_create_edit_filter_and_delete_classes_and_assign_valid_teacher(): void
    {
        $admin = $this->makeUser('admin');
        [, $major, $teacher] = $this->academicContext();
        $otherTeacher = $this->makeUser('pengajar');

        $this->actingAs($admin)->get(route('admin.kelas.create'))->assertOk();
        $this->actingAs($admin)->post(route('admin.kelas.store'), [
            'nama' => 'Kelas Integrasi',
            'jurusan_id' => $major->id,
            'pengajar_id' => $teacher->id,
        ])->assertRedirect(route('admin.kelas.index'));
        $class = Kelas::where('nama', 'Kelas Integrasi')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.kelas.index', ['jurusan_id' => $major->id, 'pengajar_id' => $teacher->id]))
            ->assertOk()->assertSee('Kelas Integrasi')->assertSee('Jumlah siswa');
        $this->actingAs($admin)->get(route('admin.kelas.show', $class))->assertOk();
        $this->actingAs($admin)->get(route('admin.kelas.edit', $class))->assertOk();
        $this->actingAs($admin)->put(route('admin.kelas.update', $class), [
            'nama' => 'Kelas Diperbarui',
            'jurusan_id' => $major->id,
            'pengajar_id' => $otherTeacher->id,
        ])->assertRedirect(route('admin.kelas.index'));
        $this->assertDatabaseHas('kelas', ['id' => $class->id, 'nama' => 'Kelas Diperbarui', 'pengajar_id' => $otherTeacher->id]);

        $student = $this->makeUser('siswa');
        KelasSiswa::create(['kelas_id' => $class->id, 'siswa_id' => $student->id]);
        $this->actingAs($admin)->delete(route('admin.kelas.destroy', $class))->assertRedirect(route('admin.kelas.index'));
        $this->assertDatabaseMissing('kelas', ['id' => $class->id]);
        $this->assertDatabaseMissing('kelas_siswa', ['kelas_id' => $class->id, 'siswa_id' => $student->id]);
    }

    public function test_class_rejects_non_teacher_and_invalid_jurusan_relations(): void
    {
        $admin = $this->makeUser('admin');
        $student = $this->makeUser('siswa');
        [, $major] = $this->academicContext();

        $response = $this->actingAs($admin)->post(route('admin.kelas.store'), [
            'nama' => 'Invalid Teacher Class',
            'jurusan_id' => $major->id,
            'pengajar_id' => $student->id,
        ]);
        $response->assertSessionHasErrors('pengajar_id');

        $this->actingAs($admin)->post(route('admin.kelas.store'), [
            'nama' => 'Invalid Major Class',
            'jurusan_id' => 999999,
            'pengajar_id' => $this->makeUser('pengajar')->id,
        ])->assertSessionHasErrors('jurusan_id');

        $this->assertDatabaseMissing('kelas', ['nama' => 'Invalid Teacher Class']);
        $this->assertDatabaseMissing('kelas', ['nama' => 'Invalid Major Class']);
    }

    public function test_admin_can_add_remove_and_move_students_atomically(): void
    {
        $admin = $this->makeUser('admin');
        [, $major, $teacher] = $this->academicContext();
        $source = Kelas::create(['jurusan_id' => $major->id, 'pengajar_id' => $teacher->id, 'nama' => 'Sumber']);
        $destination = Kelas::create(['jurusan_id' => $major->id, 'pengajar_id' => $teacher->id, 'nama' => 'Tujuan']);
        $student = $this->makeUser('siswa');

        $this->actingAs($admin)->post(route('admin.kelas.students.store', $source), ['siswa_id' => $student->id])
            ->assertRedirect();
        $this->assertDatabaseHas('kelas_siswa', ['kelas_id' => $source->id, 'siswa_id' => $student->id]);

        $this->actingAs($admin)->post(route('admin.kelas.students.move', [$source, $student]), ['kelas_tujuan_id' => $destination->id])
            ->assertRedirect(route('admin.kelas.show', $destination));
        $this->assertDatabaseMissing('kelas_siswa', ['kelas_id' => $source->id, 'siswa_id' => $student->id]);
        $this->assertDatabaseHas('kelas_siswa', ['kelas_id' => $destination->id, 'siswa_id' => $student->id]);

        $this->actingAs($admin)->delete(route('admin.kelas.students.destroy', [$destination, $student]))
            ->assertRedirect();
        $this->assertDatabaseMissing('kelas_siswa', ['kelas_id' => $destination->id, 'siswa_id' => $student->id]);
    }

    public function test_membership_rejects_wrong_roles_invalid_ids_and_duplicates(): void
    {
        $admin = $this->makeUser('admin');
        [, $major, $teacher] = $this->academicContext();
        $class = Kelas::create(['jurusan_id' => $major->id, 'pengajar_id' => $teacher->id, 'nama' => 'Anggota']);
        $nonStudent = $this->makeUser('pengajar');

        $this->actingAs($admin)->post(route('admin.kelas.students.store', $class), ['siswa_id' => $nonStudent->id])
            ->assertSessionHasErrors('siswa_id');
        $this->actingAs($admin)->post(route('admin.kelas.students.store', $class), ['siswa_id' => 999999])
            ->assertSessionHasErrors('siswa_id');

        $student = $this->makeUser('siswa');
        $this->actingAs($admin)->post(route('admin.kelas.students.store', $class), ['siswa_id' => $student->id])->assertRedirect();
        $this->actingAs($admin)->post(route('admin.kelas.students.store', $class), ['siswa_id' => $student->id])
            ->assertSessionHasErrors('siswa_id');
        $this->assertSame(1, KelasSiswa::where('kelas_id', $class->id)->where('siswa_id', $student->id)->count());
    }

    public function test_student_and_teacher_cannot_access_any_academic_admin_route(): void
    {
        foreach (['siswa', 'pengajar'] as $role) {
            $user = $this->makeUser($role);
            $this->actingAs($user)->get(route('admin.fakultas.index'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.jurusan.index'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.kelas.index'))->assertForbidden();
        }
    }

    public function test_class_with_task_cannot_be_deleted(): void
    {
        $admin = $this->makeUser('admin');
        [, $major, $teacher] = $this->academicContext();
        $class = Kelas::create(['jurusan_id' => $major->id, 'pengajar_id' => $teacher->id, 'nama' => 'Kelas Berisi Tugas']);
        Tugas::create([
            'kelas_id' => $class->id,
            'pengajar_id' => $teacher->id,
            'judul' => 'Tugas uji',
            'deskripsi' => null,
            'deadline_at' => now()->addDay(),
            'status' => 'draft',
        ]);

        $this->actingAs($admin)->delete(route('admin.kelas.destroy', $class))->assertSessionHas('error');
        $this->assertDatabaseHas('kelas', ['id' => $class->id]);
    }

    /** @return array{Fakultas, Jurusan, User} */
    private function academicContext(): array
    {
        $faculty = Fakultas::create(['nama' => 'Fakultas Akademik']);
        $major = Jurusan::create(['fakultas_id' => $faculty->id, 'nama' => 'Jurusan Akademik']);
        $teacher = $this->makeUser('pengajar');

        return [$faculty, $major, $teacher];
    }

    private function makeUser(string $role): User
    {
        static $number = 0;
        $number++;
        $user = User::factory()->create(['email' => "academic.{$number}@example.test"]);
        $user->forceFill(['role' => $role])->save();

        return $user;
    }
}
