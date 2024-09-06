<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Longman\LaravelLodash\Http\Resources\ArrayResource;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\TestCase;

use function app;

class ArrayResourceTest extends TestCase
{
    #[Test]
    public function it_should_transform_array(): void
    {
        $resource = new ArrayResource([
            'aa'  => 1,
            'aa2' => 2,
            'aa3' => 3,
        ]);

        $request = app(Request::class);
        $response = $resource->additional(['custom' => '1'])->withResourceType('customType')->toResponse($request);

        $expected = '{"data":{"id":null,"type":"customType","attributes":{"aa":1,"aa2":2,"aa3":3}},"custom":"1"}';

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame($expected, $response->content());
    }

    #[Test]
    public function it_should_return_unecaped_json(): void
    {
        $resource = new ArrayResource([
            'aa'  => 'ერთი',
            'aa2' => 'ორი',
            'aa3' => 'სამი',
        ]);

        $request = app(Request::class);
        $response = $resource->additional(['custom' => '1'])->withResourceType('customType')->toResponse($request);

        $expected = '{"data":{"id":null,"type":"customType","attributes":{"aa":"ერთი","aa2":"ორი","aa3":"სამი"}},"custom":"1"}';

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame($expected, $response->content());
    }
}
