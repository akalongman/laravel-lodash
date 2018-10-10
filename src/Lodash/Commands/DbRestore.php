<?php
/*
 * This file is part of the Laravel Lodash package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Longman\LaravelLodash\Commands;

use DB;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

class DbRestore extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:restore {file : Dump file path to restore.}
                    {--database= : The database connection to use.}
                    {--force : Force the operation to run when in production.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore mysql dump.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): void
    {
        if (! $this->confirmToProceed('Application In Production! Will be imported sql file!')) {
            return;
        }

        $db_conn = $this->getDatabase();
        $connection = DB::connection($db_conn);

        $path = $this->getFilePath();
        if (! file_exists($path)) {
            $this->error('File ' . $path . ' not found!');

            return;
        }

        $connection->unprepared(file_get_contents($path));

        $this->info('Database backup restored successfully');
    }

    protected function getDatabase(): string
    {
        $database = $this->input->getOption('database');

        return $database ?: $this->laravel['config']['database.default'];
    }

    protected function getFilePath(): string
    {
        $file = $this->input->getArgument('file');

        return base_path($file);
    }
}
