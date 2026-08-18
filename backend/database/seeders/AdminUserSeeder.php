<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * failed_doc.md §8/§10: no build meant to hold real client data should
 * carry default/example credentials (e.g. admin@admin.com / password).
 * This seeder therefore NEVER writes a fixed password — it generates a
 * random one per run and prints it once to the console, and refuses to
 * run at all when APP_ENV=production unless ADMIN_SEED_PASSWORD is set
 * explicitly (an operator decision, not a silent default).
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('email', $this->adminEmail())->exists()) {
            $this->command?->info('Admin user already exists — skipping.');

            return;
        }

        if (app()->environment('production') && ! env('ADMIN_SEED_PASSWORD')) {
            $this->command?->warn(
                'Refusing to seed a default Admin user in production without '
                .'ADMIN_SEED_PASSWORD set explicitly. Create the first Admin '
                .'via `php artisan tinker` or a one-off signed console command instead.'
            );

            return;
        }

        $password = env('ADMIN_SEED_PASSWORD') ?: Str::password(16);

        $user = User::create([
            'name' => 'Super Admin',
            'email' => $this->adminEmail(),
            'password' => $password,
            'is_active' => true,
        ]);

        $user->assignRole('Admin');

        $this->command?->warn('Seeded Admin user — CHANGE THIS PASSWORD BEFORE GO-LIVE:');
        $this->command?->line("  email:    {$user->email}");
        $this->command?->line("  password: {$password}");
    }

    private function adminEmail(): string
    {
        return env('ADMIN_SEED_EMAIL', 'admin@vishesh-textiles.example');
    }
}
