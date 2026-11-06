<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class RunBackup extends Command
{
    protected $signature = 'backup:run';

    protected $description = 'Back up the SQLite database and public uploads.';

    public function handle(): int
    {
        $connection = DB::connection();
        if ($connection->getDriverName() !== 'sqlite') {
            $this->error('The backup command currently supports SQLite databases only.');

            return self::FAILURE;
        }

        $backupDisk = Storage::disk('local');
        $publicDisk = Storage::disk('public');
        $backupDirectory = 'backups/'.now()->format('Ymd-His');
        if ($backupDisk->exists($backupDirectory)) {
            $this->error('A backup already exists for this timestamp. Run the command again in a moment.');

            return self::FAILURE;
        }

        $backupDisk->makeDirectory($backupDirectory);
        if (! $backupDisk->exists($backupDirectory)) {
            throw new RuntimeException('The backup directory could not be created.');
        }

        try {
            $databasePath = $backupDisk->path($backupDirectory.'/database.sqlite');
            $statement = $connection->getPdo()->prepare('VACUUM INTO ?');
            $statement->execute([$databasePath]);

            $uploadedFiles = 0;
            foreach ($publicDisk->allFiles() as $file) {
                $stream = $publicDisk->readStream($file);
                if (! is_resource($stream)) {
                    throw new RuntimeException("The public file [{$file}] could not be read for backup.");
                }

                try {
                    $stored = $backupDisk->put($backupDirectory.'/public/'.$file, $stream);
                } finally {
                    fclose($stream);
                }

                if (! $stored) {
                    throw new RuntimeException("The public file [{$file}] could not be written to the backup.");
                }
                $uploadedFiles++;
            }
        } catch (Throwable $exception) {
            $backupDisk->deleteDirectory($backupDirectory);

            throw $exception;
        }

        $this->info(sprintf(
            'Backup created at %s (%d public file(s)).',
            $backupDisk->path($backupDirectory),
            $uploadedFiles,
        ));

        return self::SUCCESS;
    }
}
