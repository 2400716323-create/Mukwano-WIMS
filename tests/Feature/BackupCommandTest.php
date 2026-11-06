<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PDO;
use Tests\TestCase;

class BackupCommandTest extends TestCase
{
    private string $databasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $databasePath = tempnam(sys_get_temp_dir(), 'wims-database-');
        if ($databasePath === false) {
            $this->fail('A temporary SQLite database could not be created.');
        }
        $this->databasePath = $databasePath;
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $this->databasePath,
        ]);
        DB::purge('sqlite');
        Artisan::call('migrate:fresh', ['--database' => 'sqlite', '--force' => true]);
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        if (isset($this->databasePath) && file_exists($this->databasePath)) {
            unlink($this->databasePath);
        }

        parent::tearDown();
    }

    public function test_backup_command_copies_a_consistent_database_and_public_files(): void
    {
        $user = User::factory()->create(['email' => 'backup@example.test']);
        Storage::fake('local');
        Storage::fake('public');
        Storage::disk('public')->put('system/company-logo.png', 'logo-data');
        $backupDirectory = 'backups/'.now()->format('Ymd-His');

        $this->artisan('backup:run')->assertSuccessful();

        $databaseBackup = $backupDirectory.'/database.sqlite';
        Storage::disk('local')->assertExists($databaseBackup);
        Storage::disk('local')->assertExists($backupDirectory.'/public/system/company-logo.png');
        $backupConnection = new PDO('sqlite:'.Storage::disk('local')->path($databaseBackup));
        $email = $backupConnection->query('SELECT email FROM users WHERE id = '.$user->id)->fetchColumn();

        $this->assertSame('backup@example.test', $email);
        $this->assertSame(
            'logo-data',
            Storage::disk('local')->get($backupDirectory.'/public/system/company-logo.png'),
        );
    }
}
