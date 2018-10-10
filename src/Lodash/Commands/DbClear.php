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

class DbClear extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:clear {--database= : The database connection to use.}
                {--force : Force the operation to run when in production.}
                {--pretend : Dump the SQL queries that would be run.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear database';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): void
    {
        if (! $this->confirmToProceed('Application In Production! Will be dropped all tables!')) {
            return;
        }

        $db_conn = $this->getDatabase();
        $connection = DB::connection($db_conn);

        $database = $connection->getDatabaseName();
        $tables = $connection->select('SHOW TABLES');

        if (empty($tables)) {
            $this->info('Tables not found in database "' . $database . '"');

            return;
        }

        $pretend = $this->input->getOption('pretend');
        $connection->transaction(function () use ($connection, $tables, $database, $pretend) {
            if (! $pretend) {
                $connection->statement('SET FOREIGN_KEY_CHECKS=0;');
            }

            foreach ($tables as $table) {
                foreach ($table as $key => $value) {
                    $stm = 'DROP TABLE IF EXISTS `' . $value . '`';
                    if ($pretend) {
                        $this->line("{$stm}");
                    } else {
                        $connection->statement($stm);
                        $this->comment('Table `' . $value . '` dropped');
                    }
                }
            }
            if (! $pretend) {
                $connection->statement('SET FOREIGN_KEY_CHECKS=1;');
                $this->info('All tables dropped from database "' . $database . '"!');
            }
        });
    }

    protected function getDatabase(): string
    {
        $database = $this->input->getOption('database');

        return $database ?: $this->laravel['config']['database.default'];
    }
}
