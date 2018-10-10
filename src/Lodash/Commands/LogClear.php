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

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Filesystem\Filesystem;

class LogClear extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'log:clear {--force : Force the operation to run when in production.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear log files';

    /**
     * Execute the console command.
     *
     * @param \Illuminate\Filesystem\Filesystem $filesystem
     * @return mixed
     */
    public function handle(Filesystem $filesystem): void
    {
        if (! $this->confirmToProceed('Application In Production! Will be deleted all log files from storage/log folder!')) {
            return;
        }

        $logFiles = $filesystem->allFiles(storage_path('logs'));
        if (empty($logFiles)) {
            $this->comment('Log files does not found in path ' . storage_path('logs'));

            return;
        }

        foreach ($logFiles as $file) {
            if ($file->getExtension() !== 'log') {
                continue;
            }

            $status = $filesystem->delete($file->getRealPath());
            if ($status) {
                $this->info('Successfully deleted: ' . $file);
            }
        }
    }
}
