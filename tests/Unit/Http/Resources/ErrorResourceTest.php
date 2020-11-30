<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Longman\LaravelLodash\Http\Resources\ErrorResource;
use Tests\Unit\TestCase;

use function app;

class ErrorResourceTest extends TestCase
{
    /** @test */
    public function it_should_transform_array(): void
    {
        $resource = new ErrorResource([
            'aa'  => 1,
            'aa2' => 2,
            'aa3' => 3,
        ]);

        $request = app(Request::class);
        $response = $resource->additional(['custom' => '1'])->toResponse($request);

        $expected = '{"errors":{"general":{"aa":1,"aa2":2,"aa3":3}},"custom":"1"}';

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame($expected, $response->content());
    }
}
