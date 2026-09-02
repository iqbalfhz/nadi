<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * The deliberate way to set an admin password.
 *
 * AdminUserSeeder used to do this on every deploy from a hardcoded value, which
 * doubled as the recovery path — at the cost of resetting the password behind
 * the user's back every time they pushed. This replaces that: recovery is still
 * one command away, but it only happens when someone asks for it.
 */
class SetAdminPassword extends Command
{
    protected $signature = 'nadi:admin-password
        {email? : Email akun yang mau diubah (default: akun admin utama)}
        {--generate : Buat password acak dan tampilkan sekali}';

    protected $description = 'Menetapkan ulang password akun admin — jalur pemulihan kalau password hilang';

    public function handle(): int
    {
        $email = $this->argument('email') ?? AdminUserSeeder::EMAIL;

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->components->error("Tidak ada akun dengan email {$email}.");

            return self::FAILURE;
        }

        $password = $this->resolvePassword();

        if ($password === null) {
            $this->components->error('Password dan konfirmasinya tidak sama. Tidak ada yang diubah.');

            return self::FAILURE;
        }

        // forceFill because `password` is not fillable; the model's 'hashed'
        // cast takes care of hashing on save.
        $user->forceFill(['password' => $password])->save();

        $this->components->info("Password untuk {$email} sudah diperbarui.");

        if ($this->option('generate')) {
            $this->newLine();
            $this->components->warn('Password baru (hanya ditampilkan sekali, salin sekarang):');
            $this->line("  {$password}");
            $this->newLine();
        }

        return self::SUCCESS;
    }

    private function resolvePassword(): ?string
    {
        if ($this->option('generate')) {
            return Str::password(24);
        }

        $password = $this->secret('Password baru');

        return $password === $this->secret('Ulangi password baru') ? $password : null;
    }
}
