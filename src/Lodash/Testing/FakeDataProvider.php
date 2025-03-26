<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Testing;

use Bus as BusFacade;
use Event as EventFacade;
use Faker\Factory;
use Faker\Generator;
use Http as HttpFacade;
use Illuminate\Database\Connection;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\Concerns\InteractsWithAuthentication;
use Illuminate\Http\Client\Factory as HttpFake;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Testing\Fakes\BusFake;
use Illuminate\Support\Testing\Fakes\EventFake;
use Illuminate\Support\Testing\Fakes\MailFake;
use Illuminate\Support\Testing\Fakes\NotificationFake;
use Illuminate\Support\Testing\Fakes\QueueFake;
use Mail as MailFacade;
use Notification as NotificationFacade;
use Queue as QueueFacade;

use function app;
use function config;
use function fake;

abstract readonly class FakeDataProvider
{
    use InteractsWithAuthentication;

    public static function getFakeBusInstance(): BusFake
    {
        return BusFacade::fake();
    }

    public static function getFakeEventInstance(array $eventsToFake = []): EventFake
    {
        return EventFacade::fake($eventsToFake);
    }

    public static function getFakeHttpInstance(): HttpFake
    {
        return HttpFacade::fake();
    }

    public static function getFakeNotificationInstance(): NotificationFake
    {
        return NotificationFacade::fake();
    }

    public static function getFakeMailInstance(): MailFake
    {
        return MailFacade::fake();
    }

    public static function getFakeQueueInstance(): QueueFake
    {
        return QueueFacade::fake();
    }

    public static function getFakeStorageInstance(?string $disk = null, array $config = []): FilesystemAdapter
    {
        if (! Arr::exists($config, 'url')) {
            Arr::set($config, 'url', config('app.url'));
        }

        return Storage::fake($disk, $config);
    }

    public static function getFaker($locale = Factory::DEFAULT_LOCALE): Generator
    {
        return fake($locale);
    }

    public static function getDbConnection(?string $name = null): Connection
    {
        return app('db')->connection($name);
    }
}
