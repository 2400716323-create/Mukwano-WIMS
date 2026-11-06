<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\Process\Process;
use ZipArchive;

class BackupApplication extends Command
{
    protected $signature = 'backup:run';

    protected $description = 'Create a private database and uploaded-file backup archive.';

    public function handle(): int
    {
        if (! class_exists(ZipArchive::class)) {
            $this->error('The PHP zip extension is required to create backups.');

            return self::FAILURE;
        }

        $backupDirectory = storage_path('app/private/backups');
        if (! is_dir($backupDirectory) && ! mkdir($backupDirectory, 0750, true) && ! is_dir($backupDirectory)) {
            $this->error('Unable to create the private backup directory.');

            return self::FAILURE;
        }

        $backupId = Str::uuid()->toString();
        $archivePath = $backupDirectory.DIRECTORY_SEPARATOR.'wims-'.$backupId.'.zip';
        $dumpPath = $backupDirectory.DIRECTORY_SEPARATOR.'.database-'.$backupId.'.sql';

        try {
            $this->dumpDatabase($dumpPath);
            $archive = new ZipArchive;
            if ($archive->open($archivePath, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
                throw new RuntimeException('Unable to create the backup archive.');
            }

            if (! $archive->addFile($dumpPath, 'database.sql')) {
                throw new RuntimeException('Unable to add the database dump to the archive.');
            }
            $this->addUploads($archive);
            if (! $archive->close()) {
                throw new RuntimeException('Unable to finish writing the backup archive.');
            }
            if (! is_file($archivePath) || filesize($archivePath) === 0) {
                throw new RuntimeException('The backup archive is empty.');
            }
        } catch (\Throwable $exception) {
            if (is_file($archivePath)) {
                unlink($archivePath);
            }
            $this->error($exception->getMessage());

            return self::FAILURE;
        } finally {
            if (is_file($dumpPath)) {
                unlink($dumpPath);
            }
        }

        $this->info("Backup created: {$archivePath}");

        return self::SUCCESS;
    }

    private function dumpDatabase(string $dumpPath): void
    {
        $connection = DB::connection();
        $configuration = $connection->getConfig();

        if ($connection->getDriverName() === 'sqlite') {
            $databasePath = $configuration['database'] ?? null;
            if (! is_string($databasePath) || $databasePath === ':memory:' || ! is_file($databasePath)) {
                throw new RuntimeException('A file-backed SQLite database is required for backups.');
            }

            $quotedPath = $connection->getPdo()->quote($dumpPath);
            $connection->statement("VACUUM INTO {$quotedPath}");

            return;
        }

        if (in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)) {
            $binary = $this->availableBinary(['mysqldump', 'mariadb-dump']);
            if ($binary === null) {
                throw new RuntimeException('Install mysqldump or mariadb-dump and add it to PATH to back up this database.');
            }
            $command = [
                $binary,
                '--single-transaction',
                '--skip-lock-tables',
                '--host='.($configuration['host'] ?? '127.0.0.1'),
                '--port='.(string) ($configuration['port'] ?? 3306),
                '--user='.($configuration['username'] ?? ''),
                '--result-file='.$dumpPath,
                (string) ($configuration['database'] ?? ''),
            ];
            $environment = ['MYSQL_PWD' => (string) ($configuration['password'] ?? '')];
        } elseif ($connection->getDriverName() === 'pgsql') {
            $binary = $this->availableBinary(['pg_dump']);
            if ($binary === null) {
                throw new RuntimeException('Install pg_dump and add it to PATH to back up this database.');
            }
            $command = [
                $binary,
                '--host='.($configuration['host'] ?? '127.0.0.1'),
                '--port='.(string) ($configuration['port'] ?? 5432),
                '--username='.($configuration['username'] ?? ''),
                '--file='.$dumpPath,
                (string) ($configuration['database'] ?? ''),
            ];
            $environment = ['PGPASSWORD' => (string) ($configuration['password'] ?? '')];
        } else {
            throw new RuntimeException("Database driver [{$connection->getDriverName()}] is not supported by this backup command.");
        }

        $process = new Process($command, base_path(), $environment);
        $process->run();
        if (! $process->isSuccessful()) {
            throw new RuntimeException('Database dump failed: '.trim($process->getErrorOutput()));
        }
        if (! is_file($dumpPath) || filesize($dumpPath) === 0) {
            throw new RuntimeException('The database dump command produced no data.');
        }
    }

    /**
     * @param  list<string>  $binaries
     */
    private function availableBinary(array $binaries): ?string
    {
        foreach ($binaries as $binary) {
            $process = new Process(
                [PHP_OS_FAMILY === 'Windows' ? 'where.exe' : 'which', $binary],
                base_path(),
            );
            $process->run();
            if ($process->isSuccessful()) {
                $path = trim($process->getOutput());
                if ($path !== '') {
                    return strtok($path, "\r\n") ?: null;
                }
            }
        }

        return null;
    }

    private function addUploads(ZipArchive $archive): void
    {
        $uploadsDirectory = storage_path('app/public');
        if (! is_dir($uploadsDirectory)) {
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($uploadsDirectory, RecursiveDirectoryIterator::SKIP_DOTS),
        );
        foreach ($files as $file) {
            if (! $file->isFile()) {
                continue;
            }
            $relativePath = substr($file->getPathname(), strlen($uploadsDirectory) + 1);
            if (! $archive->addFile($file->getPathname(), 'uploads/'.str_replace('\\', '/', $relativePath))) {
                throw new RuntimeException("Unable to add uploaded file [{$relativePath}] to the backup.");
            }
        }
    }
}
