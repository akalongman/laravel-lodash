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
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class AllowCorsRequests
{
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function handle(Request $request, Closure $next)
    {
        if (! $request->headers->has('Origin')) {
            return $next($request);
        }

        $origin = $request->headers->get('Origin', '');
        $host = $this->parseUrl($origin);
        if (empty($host)) {
            $this->logRequest('Origin is invalid', [
                'origin' => $origin,
                'parsed' => $host,
            ]);

            return $this->response($request, 'Origin is invalid', Response::HTTP_BAD_REQUEST);
        }

        $allowed_origins = config('lodash.cors.allow_origins', []);
        $current_app = $this->parseUrl(config('app.url', ''));
        if (! empty($host)) {
            $allowed_origins[] = $current_app;
        }

        $found = false;
        foreach ($allowed_origins as $allowed_origin) {
            if ($host === $allowed_origin || ends_with($host, '.' . $allowed_origin)) {
                $found = true;
                break;
            }
        }

        if (! $found) {
            $this->logRequest('Origin is not allowed', [
                'origin' => $origin,
                'parsed' => $host,
            ]);

            return $this->response($request, 'Origin is not allowed', Response::HTTP_METHOD_NOT_ALLOWED);
        }

        if ($request->method() === Request::METHOD_OPTIONS) {
            $allowed_headers = config('lodash.cors.allow_headers');
            $allowed_methods = config('lodash.cors.allow_methods');

            $response = $this->response($request, 'Allowed', Response::HTTP_OK);

            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');

            $response->headers->set('Access-Control-Allow-Methods', implode(',', $allowed_methods));
            $response->headers->set('Access-Control-Allow-Headers', implode(',', $allowed_headers));
            $response->headers->set('Access-Control-Max-Age', '1728000');

            return $response;
        }

        /** @var \Illuminate\Http\Response $response */
        $response = $next($request);

        $response->headers->set('Access-Control-Allow-Origin', $origin);
        $response->headers->set('Access-Control-Allow-Credentials', 'true');

        return $response;
    }

    protected function logRequest(string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    protected function parseUrl(string $url): string
    {
        $host = (string) parse_url($url, PHP_URL_HOST);

        if (starts_with($host, 'www.')) {
            $host = str_replace('www.', '', $host);
        }

        if (strpos($host, ':') !== false) {
            $host = strtok($host, ':');
        }

        return $host;
    }

    protected function response(Request $request, string $message, int $code): Response
    {
        if ($request->wantsJson()) {
            return response()->json(['message' => $message], $code);
        }

        return response($message, $code);
    }
}
