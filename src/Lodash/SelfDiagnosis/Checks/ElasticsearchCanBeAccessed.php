<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\SelfDiagnosis\Checks;

use BeyondCode\SelfDiagnosis\Checks\Check;

class ElasticsearchCanBeAccessed implements Check
{
    private $message;

    /**
     * The name of the check.
     *
     * @param array $config
     * @return string
     */
    public function name(array $config): string
    {
        return trans('lodash::checks.elasticsearch_can_be_accessed.name');
    }

    /**
     * Perform the actual verification of this check.
     *
     * @param array $config
     * @return bool
     */
    public function check(array $config): bool
    {
        try {

            /** @var \Longman\LaravelLodash\Elasticsearch\ElasticsearchManagerContract $elasticsearch */
            $elasticsearch = app($config['client']);

            if (! $elasticsearch->getClient()->ping()) {
                $this->message = trans('lodash::checks.elasticsearch_can_be_accessed.message.not_accessible', [
                    'error' => 'Ping command was failed',
                ]);

                return false;
            }
        } catch (\Throwable $e) {
            $this->message = $e->getMessage();

            return false;
        }

        return true;
    }

    /**
     * The error message to display in case the check does not pass.
     *
     * @param array $config
     * @return string
     */
    public function message(array $config): string
    {
        return trans('lodash::checks.elasticsearch_can_be_accessed.message.not_accessible', [
            'error' => $this->message,
        ]);
    }
}
