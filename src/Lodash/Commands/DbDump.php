<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Commands;

use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

use function is_dir;
use function mkdir;

use const DIRECTORY_SEPARATOR;

class DbDump extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:dump {--database= : The database connection to use.}
                    {--path= : Folder path for store database dump files.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dump database to sql file using mysqldump CLI utility.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): void
    {
        $dbConn = $this->getDatabase();
        $connection = DB::connection($dbConn);
        $dbName = $connection->getConfig('database');
        $filename = $dbName . '_' . Carbon::now()->format('Ymd_His') . '.sql';

        $path = $this->getPath($filename);

        $process = new Process('mysqldump --host=' . $connection->getConfig('host') . ' --user=' . $connection->getConfig('username') . ' --password=' . $connection->getConfig('password') . ' ' . $dbName . ' > ' . $path);
        $process->run();

        // Executes after the command finishes
        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $this->info('Database backup saved to: ' . $path);
    }

    protected function getPath(string $filename): string
    {
        $path = $this->input->getOption('path');
        if ($path) {
            if (! is_dir(base_path($path))) {
                mkdir(base_path($path), 0777, true);
            }

            $path = base_path($path . DIRECTORY_SEPARATOR . $filename);
        } else {
            $path = storage_path($filename);
        }

        return $path;
    }

    protected function getDatabase(): string
    {
        $database = $this->input->getOption('database');

        return $database ?: $this->laravel['config']['database.default'];
    }
}
