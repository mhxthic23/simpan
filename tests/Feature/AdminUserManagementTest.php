<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_teacher_account_with_hashed_password(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Pengajar Baru',
            'email' => 'pengajar.baru@example.test',
            'role' => 'pengajar',
            'password' => 'password-awal',
            'password_confirmation' => 'password-awal',
        ]);

        $teacher = User::where('email', 'pengajar.baru@example.test')->firstOrFail();
        $response->assertRedirect(route('admin.users.index'));
        $this->assertSame('pengajar', $teacher->role);
        $this->assertTrue(Hash::check('password-awal', $teacher->password));
    }

    public function test_admin_can_search_filter_and_update_user_data(): void
    {
        $admin = $this->makeUser('admin');
        $student = $this->makeUser('siswa', 'Siswa Lama', 'siswa.lama@example.test');

        $this->actingAs($admin)->get(route('admin.users.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.edit', $student))->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => 'siswa.lama', 'role' => 'siswa']))
            ->assertOk()
            ->assertSee('Siswa Lama')
            ->assertSee('siswa.lama@example.test');

        $this->actingAs($admin)->put(route('admin.users.update', $student), [
            'name' => 'Siswa Diperbarui',
            'email' => 'siswa.baru@example.test',
            'role' => 'pengajar',
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'name' => 'Siswa Diperbarui',
            'email' => 'siswa.baru@example.test',
            'role' => 'pengajar',
        ]);
    }

    public function test_admin_can_deactivate_and_reactivate_an_account_with_an_explicit_role(): void
    {
        $admin = $this->makeUser('admin');
        $teacher = $this->makeUser('pengajar');

        $this->actingAs($admin)->patch(route('admin.users.status', $teacher), [
            'active' => 0,
        ])->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $teacher->id, 'role' => null]);

        $this->actingAs($admin)->patch(route('admin.users.status', $teacher), [
            'active' => 1,
            'role' => 'pengajar',
        ])->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $teacher->id, 'role' => 'pengajar']);
    }

    public function test_students_and_teachers_cannot_access_user_management(): void
    {
        foreach (['siswa', 'pengajar'] as $role) {
            $user = $this->makeUser($role);

            $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
            $this->actingAs($user)->post(route('admin.users.store'), [])->assertForbidden();
        }
    }

    public function test_admin_cannot_change_or_disable_own_admin_role(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'siswa',
        ])->assertSessionHasErrors('role');

        $this->actingAs($admin)->patch(route('admin.users.status', $admin), [
            'active' => 0,
        ])->assertSessionHasErrors('status');

        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_bootstrap_command_creates_admin_using_interactive_input(): void
    {
        $this->artisan('simpan:make-admin')
            ->expectsQuestion('Email admin', 'admin.awal@example.test')
            ->expectsQuestion('Nama admin', 'Admin Awal')
            ->expectsQuestion('Kata sandi (minimal 8 karakter)', 'kata-sandi-bootstrap')
            ->expectsQuestion('Konfirmasi kata sandi', 'kata-sandi-bootstrap')
            ->expectsOutput('Akun admin berhasil dibuat.')
            ->assertExitCode(0);

        $admin = User::where('email', 'admin.awal@example.test')->firstOrFail();
        $this->assertSame('admin', $admin->role);
        $this->assertTrue(Hash::check('kata-sandi-bootstrap', $admin->password));
    }

    public function test_bootstrap_command_can_promote_existing_user_without_changing_password(): void
    {
        $user = $this->makeUser('siswa', 'Akun Ada', 'akun.ada@example.test');
        $oldPassword = $user->password;

        $this->artisan('simpan:make-admin')
            ->expectsQuestion('Email admin', 'akun.ada@example.test')
            ->expectsQuestion('Kata sandi akun yang sudah ada', 'password')
            ->expectsConfirmation('Tetapkan akun.ada@example.test sebagai admin?', 'yes')
            ->expectsOutput('Role admin berhasil ditetapkan pada akun yang sudah ada. Kata sandi tidak diubah.')
            ->assertExitCode(0);

        $user->refresh();
        $this->assertSame('admin', $user->role);
        $this->assertSame($oldPassword, $user->password);
    }

    private function makeUser(string $role, string $name = 'Pengguna Uji', string $email = 'user@example.test'): User
    {
        static $number = 0;
        $number++;

        $user = User::factory()->create([
            'name' => $name,
            'email' => $email === 'user@example.test' ? "user.{$number}@example.test" : $email,
        ]);
        $user->forceFill(['role' => $role])->save();

        return $user;
    }
}
