<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ResetDatabaseCommand extends Command
{
    protected $signature = 'reset:database';

    protected $description = 'Reset the database, run migrations, seed data, set up Passport clients, and clear caches';

    public function handle(): void
    {
        $this->call('migrate:fresh', ['--seed' => true]);
        $this->call('passport:keys', ['--force' => true]);
        $this->call('passport:client', [
            '--personal' => true,
            '--name' => 'App Personal Access Client',
            '--provider' => 'users',
        ]);
        $this->call('passport:client', [
            '--client' => true,
            '--name' => 'External System API Client',
        ]);
        $this->call('config:clear');
        $this->call('cache:clear');
        $this->call('route:clear');

        $this->info('Laravel database and environment reset complete.');
    }
}
