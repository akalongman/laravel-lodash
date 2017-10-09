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
use Illuminate\Http\Request;

class AllowCorsRequests
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (! $request->headers->has('Origin')) {
            return $response;
        }

        $host = parse_url($request->headers->get('Origin'), PHP_URL_HOST);
        if (empty($host)) {
            return $response;
        }

        $allowed_origins = config('lodash.cors.allow_origins');

        $found = false;
        foreach ($allowed_origins as $origin) {
            if ($found = ends_with($host, $origin)) {
                break;
            }
        }

        if (! $found) {
            return $response;
        }

        if ($request->method() === 'OPTIONS') {
            $allowed_headers = config('lodash.cors.allow_headers');
            $allowed_methods = config('lodash.cors.allow_methods');

            $response
                ->header('Access-Control-Allow-Origin', $request->headers->get('Origin'))
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Access-Control-Allow-Methods', implode(',', $allowed_methods))
                ->header('Access-Control-Allow-Headers', implode(',', $allowed_headers))
                ->header('Access-Control-Max-Age', '1728000')
                ->header('Content-Type', 'application/json');
        } else {
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set(
                'Access-Control-Allow-Origin',
                $request->headers->get('Origin')
            );
            $response->headers->set('Content-Type', 'application/json');
        }

        return $response;
    }
}
