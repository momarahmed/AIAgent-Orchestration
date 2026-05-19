<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * Idempotently provision (or reset) the demo admin / builder / viewer
 * accounts so login always works in dev — even after a `migrate:fresh`
 * or DB volume wipe. Safe to run on every container boot.
 *
 * Usage:
 *   php artisan eamcp:ensure-admin                  # ensure all demo users
 *   php artisan eamcp:ensure-admin --reset-password # force-reset passwords
 *   php artisan eamcp:ensure-admin --email=foo@x.com --password=Secret123!
 */
class EnsureAdmin extends Command
{
    protected $signature = 'eamcp:ensure-admin
        {--email= : Specific account to ensure (defaults to seeding demo users)}
        {--password= : Override password (defaults to documented demo creds)}
        {--name= : Display name when creating a single account}
        {--reset-password : Force-reset password even if the user already exists}
        {--tenants=* : Tenant slugs to attach (default: all)}';

    protected $description = 'Ensure demo users exist with known passwords (login self-heal).';

    public function handle(): int
    {
        $this->ensureRoles();

        $email = $this->option('email');
        if ($email) {
            $this->ensureUser(
                $email,
                $this->option('password') ?: 'Admin@12345',
                $this->option('name') ?: 'Demo User',
                'admin',
                true
            );
            return self::SUCCESS;
        }

        $force = (bool) $this->option('reset-password');
        $defaults = [
            ['admin@enterprise-ai-mcp.local',   'Admin@12345',   'Platform Admin',  'admin'],
            ['builder@enterprise-ai-mcp.local', 'Builder@12345', 'Agent Builder',   'builder'],
            ['viewer@enterprise-ai-mcp.local',  'Viewer@12345',  'Business Viewer', 'viewer'],
        ];

        foreach ($defaults as [$email, $password, $name, $roleName]) {
            $this->ensureUser($email, $password, $name, $roleName, $force);
        }

        $this->info('All demo users are present. Login should work.');
        return self::SUCCESS;
    }

    protected function ensureRoles(): void
    {
        $seed = [
            ['name' => 'admin',   'label' => 'Admin',          'permissions' => ['*']],
            ['name' => 'builder', 'label' => 'Builder',        'permissions' => ['agents', 'mcp', 'workflows', 'runs']],
            ['name' => 'viewer',  'label' => 'Viewer',         'permissions' => ['read']],
        ];
        foreach ($seed as $r) {
            Role::firstOrCreate(['name' => $r['name']], ['label' => $r['label'], 'permissions' => $r['permissions']]);
        }
    }

    protected function ensureUser(string $email, string $password, string $name, string $roleName, bool $forceReset): void
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            // Plain password — the User model's `hashed` cast will bcrypt it on save.
            $user = User::create(['name' => $name, 'email' => $email, 'password' => $password]);
            $this->line("  ✓ created {$email}");
        } elseif ($forceReset || ! Hash::check($password, $user->password)) {
            // Use the `hashed` cast via Eloquent so we don't double-hash.
            $user->password = $password;
            $user->save();
            $this->line("  ↻ reset password for {$email}");
        } else {
            $this->line("  · {$email} already valid");
        }

        // Attach to tenants
        $role = Role::where('name', $roleName)->first() ?? Role::where('name', 'admin')->first();
        $tenantSlugs = $this->option('tenants') ?: [];
        $tenants = empty($tenantSlugs)
            ? Tenant::all()
            : Tenant::whereIn('slug', $tenantSlugs)->get();

        foreach ($tenants as $tenant) {
            $user->tenants()->syncWithoutDetaching([
                $tenant->id => ['role_id' => $role?->id],
            ]);
        }
    }
}
