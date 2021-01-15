<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Http\Resources;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource as BaseResource;
use Longman\LaravelLodash\Eloquent\UuidAsPrimaryContract;
use Longman\LaravelLodash\Http\Resources\Response\ResourceResponse;
use Longman\LaravelLodash\Support\Arr;
use ReflectionClass;

use function array_keys;
use function array_merge;
use function array_merge_recursive;
use function is_null;
use function method_exists;
use function ucfirst;

abstract class JsonResource extends BaseResource
{
    use TransformsData;

    protected array $includedRelations = [];
    protected string $dataWrapper = 'data';
    /**
     * Default resource type, if resource is not Model
     */
    protected string $resourceType = 'object';

    public function getTransformed(): array
    {
        if ($this->resource instanceof TransformableContract) {
            return $this->transformToApi($this->resource);
        }

        return [];
    }

    public function toArray($request): array
    {
        $data = $this->getResourceData();

        $relationsData = $this->getRelationsData();

        if (! empty($relationsData)) {
            $data['relationships'] = $relationsData;
        }

        if (! empty($this->getDataWrapper())) {
            return [$this->getDataWrapper() => $data];
        }

        return $data;
    }

    public function getDataWrapper(): string
    {
        return $this->dataWrapper;
    }

    public function setDataWrapper(string $dataWrapper): void
    {
        $this->dataWrapper = $dataWrapper;
    }

    public function withRelations(array $relations): self
    {
        $this->includedRelations = array_merge($this->includedRelations, $relations);

        return $this;
    }

    public function toResponse($request): JsonResponse
    {
        return (new ResourceResponse($this))->toResponse($request);
    }

    public function withResourceType(string $resourceType): self
    {
        $this->resourceType = $resourceType;

        return $this;
    }

    public function appendAdditional(array $data): self
    {
        $this->additional = array_merge_recursive($this->additional, $data);

        return $this;
    }

    protected function getResourceData(): array
    {
        if ($this->resource instanceof TransformableContract) {
            $data = [
                'id'         => $this->getResourceId(),
                'type'       => $this->getResourceType(),
                'attributes' => $this->getTransformed(),
            ];
        } elseif (is_null($this->resource)) {
            $data = [
                'id'         => null,
                'type'       => null,
                'attributes' => [],
            ];
        } else {
            $data = [
                'id'         => null,
                'type'       => $this->getResourceType(),
                'attributes' => $this->getTransformed(),
            ];
        }

        return $data;
    }

    protected function getRelationsData(): array
    {
        if (is_null($this->resource)) {
            return [];
        }

        $relations = [];

        $relationsChain = Arr::undot($this->includedRelations);
        foreach ($relationsChain as $currentRelation => $remainingRelationChain) {
            $methodName = 'include' . ucfirst($currentRelation);

            if (! method_exists($this, $methodName)) {
                throw new Exception('Relation method does not exist: ' . $methodName);
            }

            $remainingRelations = [];
            if (! empty($remainingRelationChain)) {
                $remainingRelations = array_keys(Arr::dot($remainingRelationChain));
            }

            /** @var \Longman\LaravelLodash\Http\Resources\JsonResource $resource */
            $resource = $this->$methodName();
            if (is_null($resource)) {
                continue;
            }

            $resource->withRelations($remainingRelations);

            $relations[$currentRelation] = $resource;
        }

        return $relations;
    }

    protected function getResourceType(): string
    {
        if (! $this->resource instanceof TransformableContract) {
            return $this->resourceType;
        }

        $reflection = new ReflectionClass($this->resource->getModel());

        return $reflection->getShortName();
    }

    protected function getResourceId(): string
    {
        if ($this->resource instanceof UuidAsPrimaryContract) {
            return $this->resource->getUidString();
        }

        return (string) $this->resource->getKey();
    }
}
