<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\Unit\TestCase;

use function strpos;

class RestrictsExtraAttributesTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider provideData
     */
    public function it_should_throw_validation_error_on_extra_arguments(
        array $rules,
        array $attributes,
        array $errorAttributes
    ): void {
        $formRequest = $this->createValidator($rules, $attributes);

        try {
            $formRequest->validateResolved();
        } catch (ValidationException $e) {
            if (! empty($errorAttributes)) {
                $errors = Arr::dot($e->errors());

                foreach ($errorAttributes as $errorAttr) {
                    $found = false;
                    foreach ($errors as $error) {
                        if (strpos($error, $errorAttr) !== false) {
                            $found = true;
                            break;
                        }
                    }
                    $this->assertTrue($found, 'Failed asserting that validation errors contains "' . $errorAttr . '" string');
                }
            }
        }

        $this->assertInstanceOf(CustomRequest::class, $formRequest);
    }

    public function provideData(): array
    {
        return [
            [
                [
                    'field1' => 'required',
                    'field2' => 'required',
                ],
                [
                    'field1' => 'Some Data 1',
                    'field2' => 'Some Data 2',
                    'field3' => 'Some Data 3',
                ],
                [
                    'field3',
                ],
            ],
            [
                [
                    'field1'           => 'required',
                    'field2.subfield1' => 'required',
                    'field2.subfield2' => 'required',
                    'field2.subfield3' => 'required',
                ],
                [
                    'field1' => 'Some Data 1',
                    'field2' => [
                        'subfield1' => 'Some Data 2-1',
                        'subfield2' => 'Some Data 2-2',
                        'subfield3' => 'Some Data 2-3',
                        'subfield4' => 'Some Data 2-3',
                    ],
                    'field3' => 'Some Data 3',
                ],
                [
                    'field2.subfield4',
                    'field3',
                ],
            ],
            [
                [
                    'field1'             => 'required',
                    'field2.*.subfield1' => 'required',
                    'field2.*.subfield2' => 'required',
                    'field2.*.subfield3' => 'required',
                    'field3'             => 'required',
                    'field4'             => 'required',
                ],
                [
                    'field1' => 'Some Data 1',
                    'field2' => [
                        [
                            'subfield1' => 'Some Data 2-1',
                            'subfield2' => 'Some Data 2-2',
                        ],
                        [
                            'subfield3' => 'Some Data 2-3',
                            'subfield4' => 'Some Data 2-3',
                        ],
                    ],
                    'field3' => 'Some Data 3',
                    'field4' => 'Some Data 4',
                    'field5' => 'Some Data 3',
                ],
                [
                    'field2.*.subfield4',
                    'field5',
                ],
            ],
            [
                [
                    'field1'   => 'required',
                    'field2'   => 'required',
                    'field2.*' => 'required',
                ],
                [
                    'field1' => 'Some Data 1',
                    'field2' => [
                        [
                            'subfield1' => 'Some Data 2-1',
                            'subfield2' => 'Some Data 2-2',
                        ],
                        [
                            'subfield3' => 'Some Data 2-3',
                            'subfield4' => 'Some Data 2-3',
                        ],
                    ],
                    'field3' => 'Some Data 3',
                ],
                [
                    'field3',
                ],
            ],
        ];
    }

    private function createValidator(array $rules, array $attributes): CustomRequest
    {
        /** @var \Mockery\MockInterface|\Tests\Unit\Http\Requests\CustomRequest $formRequest */
        $formRequest = Mockery::mock(CustomRequest::class)->makePartial();
        $formRequest->shouldReceive('rules')->andReturn($rules);
        $formRequest->setContainer($this->app);
        $formRequest->initialize($attributes);
        $formRequest->setValidator(Validator::make($formRequest->all(), $rules));

        app(Translator::class)->addLines([
            'validation.restrict_extra_attributes' => 'The :attribute key is not allowed in the request body.',
        ], 'en');

        return $formRequest;
    }
}
