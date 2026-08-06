<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SeedDemoDataWhenEmpty extends Command
{
    protected $signature = 'jiwa:seed-demo-if-empty';

    protected $description = 'Seed demo users and plans only when the database has no users yet';

    public function handle(): int
    {
        if (User::query()->exists()) {
            $this->info('Users exist — skipping demo seed.');

            return self::SUCCESS;
        }

        $this->info('No users found — seeding demo data.');
        Artisan::call('db:seed', ['--force' => true]);
        $this->line(Artisan::output());

        return self::SUCCESS;
    }
}
