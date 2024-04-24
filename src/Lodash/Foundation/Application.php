<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Foundation;

use Illuminate\Foundation\Application as BaseApplication;

use function config;
use function str_replace;
use function substr;

class Application extends BaseApplication
{
    public const APP_VERSION = '1.0';

    public function getRevision(): string
    {
        $revision = (string) config('app.revision', '');
        if (str_replace('"', '', $revision) === '$REVISION') {
            return '';
        }

        return $revision;
    }

    public function getVersion(): string
    {
        $revision = $this->getRevision();
        $version = 'v' . self::APP_VERSION;
        if (! empty($revision)) {
            $version .= '.' . substr($revision, 0, 8);
        }

        return $version;
    }

    public function getUrl(): string
    {
        return (string) config('app.url', 'http://localhost');
    }

    public function isDebug(): bool
    {
        return (bool) config('app.debug', false);
    }
}
