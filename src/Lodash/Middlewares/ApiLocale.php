<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Middlewares;

use Closure;
use Illuminate\Http\Request;

use function app;
use function array_keys;
use function config;
use function setlocale;

use const LC_ALL;

class ApiLocale
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->wantsJson()) {
            $locales = config('lodash.locales', []);
            $locale = $request->getPreferredLanguage(array_keys($locales));
            app()->setLocale($locale);
        }

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $next($request);
        $locale = app()->getLocale();
        $response->headers->set('Content-Language', $locale, true);

        if (! empty($locales[$locale])) {
            setlocale(LC_ALL, $locales[$locale]['full_locale']);
        }

        return $response;
    }
}
