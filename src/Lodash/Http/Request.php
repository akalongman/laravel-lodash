<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Http;

use Illuminate\Http\Request as MainRequest;
use Illuminate\Support\Str;

use function config;
use function is_null;
use function str_contains;
use function str_replace;

class Request extends MainRequest
{
    public const CLIENT_PLATFORM_WEB = 'web';
    public const CLIENT_PLATFORM_ANDROID = 'android';
    public const CLIENT_PLATFORM_IOS = 'ios';
    private ?string $requestId = null;
    private ?string $requestPlatform = null;

    public static function getClientPlatforms(): array
    {
        return [self::CLIENT_PLATFORM_ANDROID, self::CLIENT_PLATFORM_IOS, self::CLIENT_PLATFORM_WEB];
    }

    public function getRequestId(): string
    {
        if (! is_null($this->requestId)) {
            return $this->requestId;
        }
        $this->requestId = (string) Str::uuid();

        return $this->requestId;
    }

    public function getClientPlatform(): string
    {
        if (! is_null($this->requestPlatform)) {
            return $this->requestPlatform;
        }

        $ua = Str::lower($this->userAgent());

        if (str_contains($ua, 'okhttp')) {
            $this->requestPlatform = self::CLIENT_PLATFORM_ANDROID;
        } elseif (str_contains($ua, 'darwin')) {
            $this->requestPlatform = self::CLIENT_PLATFORM_IOS;
        } else {
            $this->requestPlatform = self::CLIENT_PLATFORM_WEB;
        }

        return $this->requestPlatform;
    }

    public function getReferrerWithoutDomain(): string
    {
        return str_replace(config('app.url'), '', (string) $this->server('HTTP_REFERER'));
    }
}
