<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Resources;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Longman\LaravelLodash\Http\Resources\ArrayResource;
use Longman\LaravelLodash\Http\Resources\JsonResourceCollection;
use Tests\Unit\TestCase;

use function app;
use function array_fill;

class JsonResourceCollectionTest extends TestCase
{
    use WithFaker;

    /** @test */
    public function it_should_transform_collection_of_arrays(): void
    {
        $collection = new Collection(array_fill(0, 3, [
            'aa'  => 1,
            'aa2' => 2,
            'aa3' => 3,
        ]));

        $resource = new JsonResourceCollection($collection, ArrayResource::class);

        $request = app(Request::class);
        $response = $resource->additional(['custom' => '1'])->toResponse($request);
        $expected = '{"data":[{"id":null,"type":"object","attributes":{"aa":1,"aa2":2,"aa3":3}},{"id":null,"type":"object","attributes":{"aa":1,"aa2":2,"aa3":3}},{"id":null,"type":"object","attributes":{"aa":1,"aa2":2,"aa3":3}}],"custom":"1"}';

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame($expected, $response->content());
    }

    /** @test */
    public function it_should_transform_collection_of_models(): void
    {
        $models = [];
        for ($i = 1; $i <= 3; $i++) {
            $model = new User([
                'id'           => $i,
                'name'         => 'Custom Name ' . $i,
                'mail'         => 'Custom Mail ' . $i,
                'home_address' => 'Custom Address ' . $i,
            ]);
            $models[] = $model;
        }

        $collection = new Collection($models);

        $resource = new JsonResourceCollection($collection, UserResource::class);

        $request = app(Request::class);
        $response = $resource->additional(['custom' => '1'])->toResponse($request);
        $expected = '{"data":[{"id":"1","type":"User","attributes":{"name":"Custom Name 1","mail":"Custom Mail 1","homeAddress":"Custom Address 1","calculatedField":7}},{"id":"2","type":"User","attributes":{"name":"Custom Name 2","mail":"Custom Mail 2","homeAddress":"Custom Address 2","calculatedField":7}},{"id":"3","type":"User","attributes":{"name":"Custom Name 3","mail":"Custom Mail 3","homeAddress":"Custom Address 3","calculatedField":7}}],"custom":"1"}';
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame($expected, $response->content());

        $array = [
            'name'            => 'Custom Name 1',
            'mail'            => 'Custom Mail 1',
            'homeAddress'     => 'Custom Address 1',
            'calculatedField' => 7,
        ];
        $transformed = UserResource::transformToInternal($array);

        $this->assertSame([
            'name'             => 'Custom Name 1',
            'mail'             => 'Custom Mail 1',
            'home_address'     => 'Custom Address 1',
            'calculated_field' => 7,
        ], $transformed);
    }

    /** @test */
    public function it_should_transform_collection_of_models_with_hidden_properties(): void
    {
        $models = [];
        for ($i = 1; $i <= 3; $i++) {
            $model = new User([
                'id'                         => $i,
                'name'                       => 'Custom Name ' . $i,
                'mail'                       => 'Custom Mail ' . $i,
                'home_address'               => 'Custom Address ' . $i,
            ]);
            // Hide home_address
            $model->makeHidden(['home_address']);
            $models[] = $model;
        }

        $collection = new Collection($models);

        $resource = new JsonResourceCollection($collection, UserResource::class);

        $request = app(Request::class);
        $response = $resource->additional(['custom' => '1'])->toResponse($request);
        $expected = '{"data":[{"id":"1","type":"User","attributes":{"name":"Custom Name 1","mail":"Custom Mail 1","calculatedField":7}},{"id":"2","type":"User","attributes":{"name":"Custom Name 2","mail":"Custom Mail 2","calculatedField":7}},{"id":"3","type":"User","attributes":{"name":"Custom Name 3","mail":"Custom Mail 3","calculatedField":7}}],"custom":"1"}';
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame($expected, $response->content());

        $array = [
            'name'            => 'Custom Name 1',
            'mail'            => 'Custom Mail 1',
            'homeAddress'     => 'Custom Address 1',
            'calculatedField' => 7,
        ];
        $transformed = UserResource::transformToInternal($array);

        $this->assertSame([
            'name'             => 'Custom Name 1',
            'mail'             => 'Custom Mail 1',
            'home_address'     => 'Custom Address 1',
            'calculated_field' => 7,
        ], $transformed);
    }

    /** @test */
    public function it_should_transform_collection_of_models_with_hidden_properties_in_output(): void
    {
        $models = [];
        for ($i = 1; $i <= 3; $i++) {
            $model = new User([
                'id'           => $i,
                'name'         => 'Custom Name ' . $i,
                'mail'         => 'Custom Mail ' . $i,
                'home_address' => 'Custom Address ' . $i,
            ]);
            $models[] = $model;
        }

        $collection = new Collection($models);

        // Use resiurce where property "mail" is hidden from output
        $resource = new JsonResourceCollection($collection, UserResourceWithHidden::class);

        $request = app(Request::class);
        $response = $resource->additional(['custom' => '1'])->toResponse($request);
        $expected = '{"data":[{"id":"1","type":"User","attributes":{"name":"Custom Name 1","homeAddress":"Custom Address 1","calculatedField":7}},{"id":"2","type":"User","attributes":{"name":"Custom Name 2","homeAddress":"Custom Address 2","calculatedField":7}},{"id":"3","type":"User","attributes":{"name":"Custom Name 3","homeAddress":"Custom Address 3","calculatedField":7}}],"custom":"1"}';
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame($expected, $response->content());

        $array = [
            'name'            => 'Custom Name 1',
            'mail'            => 'Custom Mail 1',
            'homeAddress'     => 'Custom Address 1',
            'calculatedField' => 7,
        ];
        $transformed = UserResource::transformToInternal($array);

        $this->assertSame([
            'name'             => 'Custom Name 1',
            'mail'             => 'Custom Mail 1',
            'home_address'     => 'Custom Address 1',
            'calculated_field' => 7,
        ], $transformed);
    }
}
