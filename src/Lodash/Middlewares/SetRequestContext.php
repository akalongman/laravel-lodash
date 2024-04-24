<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Middlewares;

use Closure;
use Longman\LaravelLodash\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function app;

class SetRequestContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->getRequestId();
        $requestPlatform = $request->getClientPlatform();

        logger()->withContext([
            'request-id'       => $requestId,
            'request-platform' => $requestPlatform,
        ]);

        app()->instance('request-id', $requestId);
        app()->instance('request-platform', $requestPlatform);

        /** @var \Illuminate\Http\Response $response */
        $response = $next($request);
        $response->headers->set('Request-Id', $requestId, true);

        return $response;
    }
}
