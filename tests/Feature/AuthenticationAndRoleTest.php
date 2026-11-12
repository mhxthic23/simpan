<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationAndRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_creates_a_hashed_student_account_even_if_role_is_submitted(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Siswa Uji',
            'email' => 'siswa@example.test',
            'password' => 'password-kuat',
            'password_confirmation' => 'password-kuat',
            'role' => 'admin',
        ]);

        $user = User::where('email', 'siswa@example.test')->firstOrFail();

        $response->assertRedirect(route('siswa.dashboard'));
        $this->assertSame('siswa', $user->role);
        $this->assertTrue(Hash::check('password-kuat', $user->password));
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_validates_fields_in_indonesian(): void
    {
        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => '',
            'email' => 'bukan-email',
            'password' => 'singkat',
            'password_confirmation' => 'tidak-sama',
        ]);

        $response->assertRedirect(route('register'))
            ->assertSessionHasErrors([
                'name' => 'Nama wajib diisi.',
                'email' => 'Masukkan alamat email yang valid.',
                'password' => 'Kata sandi minimal 8 karakter.',
            ]);
    }

    public function test_correct_credentials_login_and_redirect_by_role(): void
    {
        $user = User::factory()->create(['email' => 'pengajar@example.test']);
        $user->forceFill(['role' => 'pengajar'])->save();

        $response = $this->post(route('login.store'), [
            'email' => 'pengajar@example.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('pengajar.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_credentials_return_indonesian_error(): void
    {
        User::factory()->create(['email' => 'siswa@example.test']);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => 'siswa@example.test',
            'password' => 'salah',
        ]);

        $response->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'Alamat email atau kata sandi tidak cocok.']);
        $this->assertGuest();
    }

    public function test_logout_ends_the_authenticated_session(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_each_role_can_open_only_its_own_dashboard(): void
    {
        $dashboardRoutes = [
            'siswa' => 'siswa.dashboard',
            'pengajar' => 'pengajar.dashboard',
            'admin' => 'admin.dashboard',
        ];

        foreach ($dashboardRoutes as $role => $allowedRoute) {
            $user = User::factory()->create();
            $user->forceFill(['role' => $role])->save();

            foreach ($dashboardRoutes as $candidateRole => $routeName) {
                $response = $this->actingAs($user)->get(route($routeName));

                if ($candidateRole === $role) {
                    $response->assertOk();
                } else {
                    $response->assertForbidden();
                }
            }

            $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route($allowedRoute));
        }
    }

    public function test_user_without_role_cannot_open_any_dashboard(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => null])->save();

        $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('siswa.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('pengajar.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_user_without_role_is_not_left_authenticated_after_login(): void
    {
        $user = User::factory()->create(['email' => 'tanpa-role@example.test']);

        $response = $this->post(route('login.store'), [
            'email' => 'tanpa-role@example.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'Akun belum memiliki role aktif. Hubungi administrator.']);
        $this->assertGuest();
    }

    public function test_authenticated_users_are_redirected_away_from_login_and_registration(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => 'siswa'])->save();

        $this->actingAs($user)->get(route('login'))->assertRedirect(route('dashboard'));
        $this->actingAs($user)->get(route('register'))->assertRedirect(route('dashboard'));
    }
}
