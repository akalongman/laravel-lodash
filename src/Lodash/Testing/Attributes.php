<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Testing;

use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;

use function array_key_exists;
use function array_replace_recursive;
use function explode;
use function str_starts_with;

class Attributes implements Arrayable
{
    private const RELATION_MARKER = 'relations:';
    private array $params;
    private array $attributes = [];
    /** @var self[] */
    private array $relations = [];
    private string $relationName;
    private int $count;

    public function __construct(array $params, string $relationName = 'root', int $count = 1)
    {
        $this->params = $params;
        $this->relationName = $relationName;
        $this->count = $count;
        $this->parseParameters($params);
    }

    public function getAttributes(array $extraAttrs = []): array
    {

        return array_replace_recursive($this->attributes, $extraAttrs);
    }

    public function hasAttribute(string $name): bool
    {

        return array_key_exists($name, $this->attributes);
    }

    /**
     * @return mixed
     */
    public function getAttribute(string $name, $default = null)
    {

        return $this->attributes[$name] ?? $default;
    }

    public function setAttribute(string $name, $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function getCount(): int
    {

        return $this->count;
    }

    public function getRelationName(): string
    {

        return $this->relationName;
    }

    public function getRelations(): array
    {
        return $this->relations;
    }

    public function hasRelation(string $name): bool
    {
        return array_key_exists($name, $this->getRelations());
    }

    public function getRelation(string $name): self
    {
        if (! $this->hasRelation($name)) {
            throw new InvalidArgumentException('Relation "' . $name . '" does not found');
        }

        return $this->getRelations()[$name];
    }

    public function addRelation(string $name, int $count = 1, array $data = []): void
    {
        $this->params = array_replace_recursive($this->params, [self::RELATION_MARKER . $name . ':' . $count => $data]);
    }

    public function toArray(array $extraParams = []): array
    {
        return array_replace_recursive($this->params, $extraParams);
    }

    private function parseParameters(array $params): void
    {
        $attributes = [];
        $relations = [];
        foreach ($params as $key => $data) {
            if (str_starts_with($key, self::RELATION_MARKER)) {
                $ex = explode(':', $key);
                if (! isset($ex[1])) {
                    throw new InvalidArgumentException('Relation is empty');
                }

                $relName = $ex[1];
                $count = 1;
                if (isset($ex[2])) {
                    $count = (int) $ex[2];
                }
                $attrs = $params[$key];

                $relations[$relName] = new self($attrs, $relName, $count);
            } else {
                $attributes[$key] = $data;
            }
        }

        $this->attributes = $attributes;
        $this->relations = $relations;
    }
}
