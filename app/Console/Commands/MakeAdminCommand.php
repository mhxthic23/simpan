<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class MakeAdminCommand extends Command
{
    protected $signature = 'simpan:make-admin';

    protected $description = 'Membuat akun admin awal atau menetapkan role admin ke akun yang sudah ada';

    public function handle(): int
    {
        $email = trim((string) $this->ask('Email admin'));
        $validator = Validator::make(['email' => $email], [
            'email' => ['required', 'email', 'max:255'],
        ]);

        if ($validator->fails()) {
            $this->error('Masukkan alamat email yang valid.');
            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user !== null) {
            $password = (string) $this->secret('Kata sandi akun yang sudah ada');
            if (! Hash::check($password, $user->password)) {
                $this->error('Kata sandi akun tidak cocok. Role tidak diubah.');
                return self::FAILURE;
            }

            if (! $this->confirm("Tetapkan {$email} sebagai admin?", false)) {
                $this->info('Tidak ada perubahan.');
                return self::SUCCESS;
            }

            $user->role = 'admin';
            $user->save();
            $this->info('Role admin berhasil ditetapkan pada akun yang sudah ada. Kata sandi tidak diubah.');

            return self::SUCCESS;
        }

        $name = trim((string) $this->ask('Nama admin'));
        if ($name === '' || mb_strlen($name) > 255) {
            $this->error('Nama wajib diisi dan maksimal 255 karakter.');
            return self::FAILURE;
        }

        $password = (string) $this->secret('Kata sandi (minimal 8 karakter)');
        $confirmation = (string) $this->secret('Konfirmasi kata sandi');

        if (mb_strlen($password) < 8 || ! hash_equals($password, $confirmation)) {
            $this->error('Kata sandi minimal 8 karakter dan konfirmasinya harus cocok.');
            return self::FAILURE;
        }

        $user = new User();
        $user->name = $name;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->role = 'admin';
        $user->save();

        $this->info('Akun admin berhasil dibuat.');

        return self::SUCCESS;
    }
}
