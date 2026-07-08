<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Testing;

use InvalidArgumentException;
use Longman\LaravelLodash\Support\Str;

use function array_shift;
use function explode;
use function implode;
use function is_array;
use function is_int;
use function is_string;
use function lcfirst;
use function method_exists;
use function property_exists;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function substr;
use function trim;

abstract class DataStructuresProvider
{
    protected static function resolveItemStructure(string $relationItem): array
    {
        $method = 'get' . $relationItem . 'Structure';
        if (method_exists(static::class, $method)) {
            return static::$method();
        }

        $property = lcfirst($relationItem) . 'Structure';
        if (! property_exists(static::class, $property)) {
            throw new InvalidArgumentException('Getter method for structure "' . $relationItem . '" does not exists');
        }

        return static::$$property;
    }

    /**
     * @return array<string, array{structure: ?string, collection: bool, children: array}>
     */
    protected static function normalizeRelations(array $relations): array
    {
        $tree = [];

        foreach ($relations as $key => $value) {
            if (is_int($key) && is_string($value)) {
                $branch = self::normalizePath($value, []);
            } elseif (is_string($key) && is_array($value)) {
                $branch = self::normalizePath($key, self::normalizeRelations($value));
            } elseif (is_string($key) && is_string($value)) {
                throw new InvalidArgumentException(
                    'Invalid relation declaration "' . $key . '" => "' . $value . '". Use "' . $key . ':' . $value . '" instead.',
                );
            } else {
                throw new InvalidArgumentException('Invalid relation declaration.');
            }

            $tree = self::mergeRelationTrees($tree, $branch);
        }

        return $tree;
    }

    /**
     * @return array<string, array{structure: ?string, collection: bool, children: array}>
     */
    protected static function normalizePath(string $path, array $children): array
    {
        $segments = explode('.', $path);
        $first = array_shift($segments);
        [$name, $structure, $isCollection] = self::parseSegment($first);

        $childTree = $segments === []
            ? $children
            : self::normalizePath(implode('.', $segments), $children);

        return [
            $name => [
                'structure' => $structure,
                'collection' => $isCollection,
                'children' => $childTree,
            ],
        ];
    }

    /**
     * @return array{0: string, 1: ?string, 2: bool}
     */
    protected static function parseSegment(string $segment): array
    {
        if (str_starts_with($segment, '[')) {
            $inner = trim($segment, '[]');
            if (str_contains($inner, ':')) {
                [$innerName, $innerStructure] = explode(':', $inner, 2);
                $hint = $innerName . '[]:' . $innerStructure;
            } else {
                $hint = $inner . '[]';
            }

            throw new InvalidArgumentException(
                'Legacy relation syntax "' . $segment . '" is not supported. Use "' . $hint . '" instead.',
            );
        }

        $structure = null;
        $name = $segment;
        if (str_contains($segment, ':')) {
            [$name, $structure] = explode(':', $segment, 2);
        }

        if ($structure !== null && (str_contains($structure, '[') || str_contains($structure, ']'))) {
            throw new InvalidArgumentException(
                'Invalid relation segment "' . $segment . '". Use "'
                . trim($name, '[]') . '[]:' . trim($structure, '[]') . '" instead.',
            );
        }

        $isCollection = false;
        if (str_ends_with($name, '[]')) {
            $isCollection = true;
            $name = substr($name, 0, -2);
        }

        if ($name === '' || $structure === '' || str_contains($name, '[') || str_contains($name, ']')) {
            throw new InvalidArgumentException('Invalid relation segment "' . $segment . '".');
        }

        return [$name, $structure, $isCollection];
    }

    /**
     * @param array<string, array{structure: ?string, collection: bool, children: array}> $tree
     * @param array<string, array{structure: ?string, collection: bool, children: array}> $branch
     *
     * @return array<string, array{structure: ?string, collection: bool, children: array}>
     */
    protected static function mergeRelationTrees(array $tree, array $branch): array
    {
        foreach ($branch as $name => $node) {
            if (! isset($tree[$name])) {
                $tree[$name] = $node;
                continue;
            }

            $existing = $tree[$name];

            if ($existing['collection'] !== $node['collection']) {
                throw new InvalidArgumentException(
                    'Relation "' . $name . '" is declared both as a collection and as a single item.',
                );
            }

            if ($existing['structure'] !== null && $node['structure'] !== null && $existing['structure'] !== $node['structure']) {
                throw new InvalidArgumentException(
                    'Relation "' . $name . '" has conflicting structure names "' . $existing['structure'] . '" and "' . $node['structure'] . '".',
                );
            }

            $tree[$name] = [
                'structure' => $existing['structure'] ?? $node['structure'],
                'collection' => $existing['collection'],
                'children' => self::mergeRelationTrees($existing['children'], $node['children']),
            ];
        }

        return $tree;
    }

    /**
     * @param array<string, array{structure: ?string, collection: bool, children: array}> $tree
     */
    protected static function applyRelationTree(array $structure, array $tree): array
    {
        foreach ($tree as $name => $node) {
            $relationStructure = static::resolveItemStructure($node['structure'] ?? $name);

            if ($node['children'] !== []) {
                $relationStructure = static::applyRelationTree($relationStructure, $node['children']);
            }

            $structure['relationships'][$name]['data'] = $node['collection']
                ? ['*' => $relationStructure]
                : $relationStructure;
        }

        return $structure;
    }

    public static function __callStatic(string $name, array $arguments): array
    {
        $property = lcfirst(Str::substr($name, 3));
        if (! property_exists(static::class, $property)) {
            throw new InvalidArgumentException('Property "' . $property . '" does not exists');
        }
        $structure = static::$$property;

        $tree = self::normalizeRelations($arguments[0] ?? []);

        return static::applyRelationTree($structure, $tree);
    }
}
