<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

use function implode;
use function parse_url;
use function str_replace;
use function strpos;
use function strtok;

use const PHP_URL_HOST;

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

        $allowedOrigins = config('lodash.cors.allow_origins', []);
        $currentApp = $this->parseUrl((string) config('app.url', ''));
        if (! empty($host)) {
            $allowedOrigins[] = $currentApp;
        }

        $found = false;
        foreach ($allowedOrigins as $allowedOrigin) {
            if ($host === $allowedOrigin || Str::endsWith($host, '.' . $allowedOrigin)) {
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
            $allowedHeaders = config('lodash.cors.allow_headers', []);
            $allowedMethods = config('lodash.cors.allow_methods', []);

            $response = $this->response($request, 'Allowed', Response::HTTP_OK);

            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');

            $response->headers->set('Access-Control-Allow-Methods', implode(',', $allowedMethods));
            $response->headers->set('Access-Control-Allow-Headers', implode(',', $allowedHeaders));
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

        if (Str::startsWith($host, 'www.')) {
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
