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

class XssSecurity
{
    public function handle(Request $request, Closure $next)
    {
        /** @var \Illuminate\Http\Response $response */
        $response = $next($request);

        $request_uri = $request->getUri();
        $excluded = config('lodash.xss.exclude_uris');
        if (! empty($excluded)) {
            foreach ($excluded as $uri) {
                if (strpos($request_uri, $uri) !== false) {
                    return $response;
                }
            }
        }

        /** @see http://blogs.msdn.com/b/ieinternals/archive/2010/03/30/combating-clickjacking-with-x-frame-options.aspx */
        $response->headers->set('X-Frame-Options', config('lodash.xss.x_frame_options'), true);

        /** @see http://msdn.microsoft.com/en-us/library/ie/gg622941(v=vs.85).aspx */
        $response->headers->set('X-Content-Type-Options', config('lodash.xss.x_content_type_options'), true);

        /** @see http://msdn.microsoft.com/en-us/library/dd565647(v=vs.85).aspx */
        $response->headers->set('X-XSS-Protection', config('lodash.xss.x_xss_protection'), true);

        return $response;
    }
}
