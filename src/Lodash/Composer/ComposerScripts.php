<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Composer;

use Composer\Script\Event;

use function dirname;

class ComposerScripts
{
    public static function createPackageHash(Event $event): void
    {
        $baseDir = dirname($event->getComposer()->getConfig()->get('vendor-dir'));

        $composer = new ComposerChecker($baseDir);
        $composer->createHash();
        $event->getIO()->write('<info>The composer.lock hash saved in the "vendor/composer.hash" file.</info>');
    }
}
