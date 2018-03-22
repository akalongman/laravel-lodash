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
use Illuminate\Http\Response;

class AllowCorsRequests
{
    public function handle(Request $request, Closure $next)
    {
        /** @var \Illuminate\Http\Response $response */
        $response = $next($request);

        if (! $request->headers->has('Origin')) {
            return $response;
        }

        $host = parse_url($request->headers->get('Origin'), PHP_URL_HOST);
        if (empty($host)) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);

            return $response;
        }

        $allowed_origins = config('lodash.cors.allow_origins', []);
        $host = config('app.url');
        if (! empty($host)) {
            $allowed_origins[] = $host;
        }

        $found = false;
        foreach ($allowed_origins as $origin) {
            if ($found = ends_with($host, $origin)) {
                break;
            }
        }

        if (! $found) {
            $response->setStatusCode(Response::HTTP_METHOD_NOT_ALLOWED);

            return $response;
        }

        $response->headers->set('Access-Control-Allow-Origin', $request->headers->get('Origin'));
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Content-Type', 'application/json');

        if ($request->method() === Request::METHOD_OPTIONS) {
            $allowed_headers = config('lodash.cors.allow_headers');
            $allowed_methods = config('lodash.cors.allow_methods');

            $response->headers->set('Access-Control-Allow-Methods', implode(',', $allowed_methods));
            $response->headers->set('Access-Control-Allow-Headers', implode(',', $allowed_headers));
            $response->headers->set('Access-Control-Max-Age', '1728000');
        }

        return $response;
    }
}
