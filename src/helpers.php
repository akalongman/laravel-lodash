<?php

declare(strict_types=1);

if (! function_exists('p')) {
    function p(...$values): void
    {
        /** @var \Barryvdh\Debugbar\LaravelDebugbar $debugbar */
        $debugbar = app('debugbar');
        foreach ($values as $value) {
            $debugbar->addMessage($value, 'debug');
        }
    }
}

if (! function_exists('get_db_query')) {
    function get_db_query(): string
    {
        if (! app()->bound('debugbar')) {
            return '';
        }

        /** @var \Barryvdh\Debugbar\LaravelDebugbar $debugbar */
        $debugbar = app('debugbar');

        try {
            $collector = $debugbar->getCollector('queries');
        } catch (Throwable $e) {
            return '';
        }

        $queries = $collector->collect();
        if (empty($queries['statements'])) {
            return '';
        }

        $query = end($queries['statements']);

        return $query['sql'];
    }
}

if (! function_exists('get_db_queries')) {
    function get_db_queries(): array
    {
        if (! app()->bound('debugbar')) {
            return [];
        }
        /** @var \Barryvdh\Debugbar\LaravelDebugbar $debugbar */
        $debugbar = app('debugbar');

        try {
            $collector = $debugbar->getCollector('queries');
        } catch (Throwable $e) {
            return [];
        }

        $queries = $collector->collect();
        if (empty($queries['statements'])) {
            return [];
        }

        $list = [];
        foreach ($queries['statements'] as $query) {
            $list[] = $query['sql'];
        }

        return $list;
    }
}
