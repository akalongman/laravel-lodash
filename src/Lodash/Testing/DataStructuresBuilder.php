<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Testing;

use InvalidArgumentException;

use function array_shift;
use function explode;
use function is_callable;
use function str_contains;
use function str_starts_with;
use function trim;

abstract class DataStructuresBuilder
{
    /**
     * Caller object instance, where defined data structure getters like caller::getUserStructure()
     */
    protected static object $caller;

    public static function includeNestedRelations(object $caller, array &$item, array $relations): void
    {
        if (empty($relations)) {
            return;
        }

        self::setCallerObject($caller);

        foreach ($relations as $relation) {
            $parentRelations = explode('.', $relation);
            self::includeNestedRelation($item, $parentRelations);
        }
    }

    protected static function includeNestedRelation(array &$item, array $parentRelations = []): void
    {
        $currentRelation = array_shift($parentRelations);
        /* check if we reached bottom of the relation tree, if so add new relation to the tree*/
        if (empty($parentRelations)) {
            // Set relation collection by default to false
            $isRelationCollection = false;
            if (str_starts_with($currentRelation, '[')) {
                $currentRelation = trim($currentRelation, '[]');
                $isRelationCollection = true;
            }

            if (str_contains($currentRelation, ':')) {
                [$relationKey, $relationItem] = explode(':', $currentRelation);
            } else {
                $relationKey = $currentRelation;
                $relationItem = $currentRelation;
            }

            if ($isRelationCollection) {
                $item['relationships'][$relationKey]['data'][0] = self::getItemStructure($relationItem);
            } else {
                $item['relationships'][$relationKey]['data'] = self::getItemStructure($relationItem);
            }
        } else {
            // get to the bottom of the relation tree
            if (str_starts_with($currentRelation, '[')) {
                $currentRelation = trim($currentRelation, '[]');
                self::includeNestedRelation($item['relationships'][$currentRelation]['data'][0], $parentRelations);
            } else {
                self::includeNestedRelation($item['relationships'][$currentRelation]['data'], $parentRelations);
            }
        }
    }

    protected static function getItemStructure(string $relationItem): array
    {
        $caller = self::getCallerObject();
        $method = 'get' . $relationItem . 'Structure';
        if (! is_callable([$caller, $method])) {
            throw new InvalidArgumentException('Getter method for structure "' . $relationItem . '" does not exists');
        }

        return $caller->$method();
    }

    protected static function setCallerObject(object $caller): void
    {
        self::$caller = $caller;
    }

    protected static function getCallerObject(): object
    {
        return self::$caller;
    }
}
