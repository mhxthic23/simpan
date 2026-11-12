<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    private const ROLES = ['siswa', 'pengajar', 'admin'];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', Rule::in([...self::ROLES, 'nonaktif'])],
        ], [
            'q.string' => 'Kata pencarian tidak valid.',
            'q.max' => 'Kata pencarian maksimal 255 karakter.',
            'role.in' => 'Filter role tidak valid.',
        ]);

        $users = User::query()
            ->when($filters['q'] ?? null, function (Builder $query, string $term): void {
                $query->where(function (Builder $query) use ($term): void {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->when($filters['role'] ?? null, function (Builder $query, string $role): void {
                $role === 'nonaktif'
                    ? $query->whereNull('role')
                    : $query->where('role', $role);
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'filters'));
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(self::ROLES)],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], $this->messages());

        $user = new User();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = Hash::make($data['password']);
        $user->role = $data['role'];
        $user->save();

        return redirect()->route('admin.users.index')
            ->with('status', 'Akun pengguna berhasil dibuat.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $isSelf = $request->user()->is($user);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => [$isSelf ? 'nullable' : 'required', Rule::in([...self::ROLES, 'nonaktif'])],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], $this->messages());

        $newRole = $isSelf ? $user->role : ($data['role'] === 'nonaktif' ? null : $data['role']);

        if ($isSelf && isset($data['role']) && $data['role'] !== 'admin') {
            return back()->withErrors(['role' => 'Anda tidak dapat mengubah role atau menonaktifkan akun admin yang sedang digunakan.'])->withInput();
        }

        if ($user->role === 'admin' && $newRole !== 'admin' && $this->isLastAdmin($user)) {
            return back()->withErrors(['role' => 'Role admin terakhir tidak dapat diubah atau dinonaktifkan.'])->withInput();
        }

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->role = $newRole;
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        return redirect()->route('admin.users.index')
            ->with('status', 'Data pengguna berhasil diperbarui.');
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'active' => ['required', 'boolean'],
            'role' => ['nullable', Rule::in(self::ROLES)],
        ], [
            'active.required' => 'Status akun wajib ditentukan.',
            'active.boolean' => 'Status akun tidak valid.',
            'role.in' => 'Role yang dipilih tidak valid.',
        ]);

        $activating = (bool) $data['active'];

        if ($request->user()->is($user) && ! $activating) {
            return back()->withErrors(['status' => 'Anda tidak dapat menonaktifkan akun admin yang sedang digunakan.']);
        }

        if ($activating) {
            $role = $user->role ?? ($data['role'] ?? null);
            if ($role === null) {
                return back()->withErrors(['role' => 'Pilih role untuk mengaktifkan akun ini.']);
            }

            $user->role = $role;
            $message = 'Akun berhasil diaktifkan.';
        } else {
            if ($user->role === 'admin' && $this->isLastAdmin($user)) {
                return back()->withErrors(['status' => 'Admin terakhir tidak dapat dinonaktifkan.']);
            }

            $user->role = null;
            $message = 'Akun berhasil dinonaktifkan.';
        }

        $user->save();

        return back()->with('status', $message);
    }

    private function isLastAdmin(User $user): bool
    {
        return $user->role === 'admin'
            && User::query()->where('role', 'admin')->count() <= 1;
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'name.string' => 'Nama harus berupa teks.',
            'name.max' => 'Nama maksimal 255 karakter.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Masukkan alamat email yang valid.',
            'email.max' => 'Alamat email maksimal 255 karakter.',
            'email.unique' => 'Alamat email sudah digunakan.',
            'role.required' => 'Role pengguna wajib dipilih.',
            'role.in' => 'Role yang dipilih tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.string' => 'Kata sandi tidak valid.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ];
    }
}
