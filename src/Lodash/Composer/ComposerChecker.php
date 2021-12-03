<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Composer;

use InvalidArgumentException;
use RuntimeException;

use function chmod;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function md5;

use const DIRECTORY_SEPARATOR;

class ComposerChecker
{
    public function __construct(
        private string $baseDir,
        private string $lockPath = 'composer.lock',
        private string $hashPath = 'vendor/composer.hash',
    ) {
        //
    }

    public function createHash(): void
    {
        file_put_contents($this->getHashPath(), $this->getHash($this->getContent($this->getJsonPath())));
        chmod($this->getHashPath(), 0777);
    }

    public function checkHash(): void
    {
        if (! $this->validateHash()) {
            throw new RuntimeException('The vendor folder is not in sync with the composer.lock file, it is recommended that you run `composer install`.');
        }
    }

    public function validateHash(): bool
    {
        if (! file_exists($this->getHashPath())) {
            return false;
        }

        $hash = $this->getContent($this->getHashPath());
        $currentHash = $this->getHash($this->getContent($this->getJsonPath()));

        return $hash === $currentHash;
    }

    private function getJsonPath(): string
    {
        return $this->baseDir . DIRECTORY_SEPARATOR . $this->lockPath;
    }

    private function getHashPath(): string
    {
        return $this->baseDir . DIRECTORY_SEPARATOR . $this->hashPath;
    }

    private function getHash(string $content): string
    {
        return md5($content);
    }

    private function getContent(string $file): string
    {
        if (! file_exists($file)) {
            throw new InvalidArgumentException('File ' . $file . ' does not found!');
        }

        return file_get_contents($file);
    }
}
