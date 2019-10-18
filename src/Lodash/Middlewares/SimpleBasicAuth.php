<?php

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
