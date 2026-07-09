<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class BackupDatabase extends Command
{
    protected $signature = 'pos:backup';

    protected $description = 'Dump the POS database to storage/app/backups (keeps the last 30)';

    public function handle(): int
    {
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);

        $file = $dir.'/pos-backup-'.now()->format('Y-m-d_His').'.sql';

        $mysqldump = env('MYSQLDUMP_PATH') ?: $this->findMysqldump();

        if (! $mysqldump) {
            $this->error('mysqldump not found. Set MYSQLDUMP_PATH in .env');

            return self::FAILURE;
        }

        $process = new Process([
            $mysqldump,
            '--host='.config('database.connections.mysql.host'),
            '--port='.config('database.connections.mysql.port'),
            '--user='.config('database.connections.mysql.username'),
            ...(config('database.connections.mysql.password')
                ? ['--password='.config('database.connections.mysql.password')]
                : []),
            '--single-transaction',
            '--result-file='.$file,
            config('database.connections.mysql.database'),
        ]);

        $process->setTimeout(300)->run();

        if (! $process->isSuccessful() || ! File::exists($file) || File::size($file) === 0) {
            $this->error('Backup failed: '.$process->getErrorOutput());
            File::delete($file);

            return self::FAILURE;
        }

        // Keep only the newest 30 backups
        collect(File::files($dir))
            ->filter(fn ($f) => str_starts_with($f->getFilename(), 'pos-backup-'))
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->slice(30)
            ->each(fn ($f) => File::delete($f->getPathname()));

        $this->info('Backup saved: '.$file.' ('.round(File::size($file) / 1024).' KB)');

        return self::SUCCESS;
    }

    protected function findMysqldump(): ?string
    {
        $candidates = [
            '/Applications/XAMPP/xamppfiles/bin/mysqldump', // macOS XAMPP (dev)
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',          // Windows XAMPP (her laptop)
            '/usr/local/bin/mysqldump',
            '/usr/bin/mysqldump',
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Fall back to PATH
        $which = new Process([PHP_OS_FAMILY === 'Windows' ? 'where' : 'which', 'mysqldump']);
        $which->run();

        return $which->isSuccessful() ? trim($which->getOutput()) : null;
    }
}
