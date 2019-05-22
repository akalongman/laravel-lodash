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

namespace Longman\LaravelLodash\Middlewares;

use Closure;
use Illuminate\Http\Response;

use function config;
use function response;

class SimpleBasicAuth
{
    public function handle($request, Closure $next)
    {
        $config = config('auth.simple', []);

        if (! empty($config['enabled'])) {
            if ($request->getUser() !== $config['user'] || $request->getPassword() !== $config['password']) {
                $header = ['WWW-Authenticate' => 'Basic'];

                return response('You have to supply your credentials to access this resource.', Response::HTTP_UNAUTHORIZED, $header);
            }
        }

        return $next($request);
    }
}
