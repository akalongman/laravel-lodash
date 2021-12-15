<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Elasticsearch;

use Exception;

class ElasticsearchException extends Exception
{
    protected array $errors = [];

    public function __construct(string $message = '', array $errors = [])
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
