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

class UserAdd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:add {email} {password?}
                {--guard= : The guard to use.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new user.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): void
    {
        $guard = $this->getGuard();
        $config = config('auth');

        if (! isset($config['guards'][$guard]['provider'])) {
            $this->error('Provider not found for guard "' . $guard . '"!');

            return;
        }
        $provider = $config['guards'][$guard]['provider'];
        if (! isset($config['providers'][$provider]['model'])) {
            $this->error('Model not found for provider "' . $provider . '"!');

            return;
        }
        $model = $config['providers'][$provider]['model'];
        $user = new $model;

        $email = $this->argument('email');
        $password = $this->argument('password');
        if (! $password) {
            if ($this->confirm('Let system generate password?', true)) {
                $password = str_random(16);
            } else {
                $password = $this->secret('Please enter new password');
            }
        }
        $crypted_password = bcrypt($password);
        $user->create([
            'email'    => $email,
            'password' => $crypted_password,
        ]);

        $this->comment('User successfully created');
        $this->info('E-Mail: ' . $email);
        $this->info('Password: ' . $password);
    }

    protected function getGuard(): string
    {
        $guard = $this->input->getOption('guard');

        return $guard ?? config('auth.defaults.guard');
    }

}
