<?php

declare(strict_types=1);

namespace Tests\Unit\Testing;

use Illuminate\Testing\Assert as PHPUnit;
use Tests\Unit\TestCase;

use function is_array;

class DataStructuresBuilderTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider provideData
     */
    public function it_should_return_corrct_data_structures(array $structure, array $data): void
    {
        $this->assertStructure($structure, $data);
    }

    public function provideData(): array
    {
        $provider = new ItemStructuresProvider();

        return [
            'user'                                      => [
                $provider->getUserStructure(),
                [
                    'id'         => '',
                    'type'       => '',
                    'attributes' => [
                        'uid'       => '',
                        'firstName' => '',
                        'lastName'  => '',
                        'fullName'  => '',
                        'email'     => '',
                        'avatar'    => '',
                        'photoUrl'  => '',
                    ],
                ],
            ],
            'user_with_profiles'                        => [
                $provider->getUserStructure(['[profiles:userProfile]']),
                [
                    'id'            => '',
                    'type'          => '',
                    'attributes'    => [
                        'uid'       => '',
                        'firstName' => '',
                        'lastName'  => '',
                        'fullName'  => '',
                        'email'     => '',
                        'avatar'    => '',
                        'photoUrl'  => '',
                    ],
                    'relationships' => [
                        'profiles' => [
                            'data' => [
                                [
                                    'type'       => '',
                                    'id'         => '',
                                    'attributes' => [
                                        'type'   => '',
                                        'degree' => '',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'user_with_profiles_and_status'             => [
                $provider->getUserStructure([
                    '[profiles:userProfile]',
                    '[profiles].profileStatus',
                ]),
                [
                    'id'            => '',
                    'type'          => '',
                    'attributes'    => [
                        'uid'       => '',
                        'firstName' => '',
                        'lastName'  => '',
                        'fullName'  => '',
                        'email'     => '',
                        'avatar'    => '',
                        'photoUrl'  => '',
                    ],
                    'relationships' => [
                        'profiles' => [
                            'data' => [
                                [
                                    'type'          => '',
                                    'id'            => '',
                                    'attributes'    => [
                                        'type'   => '',
                                        'degree' => '',
                                    ],
                                    'relationships' => [
                                        'profileStatus' => [
                                            'data' => [
                                                'type'       => '',
                                                'id'         => '',
                                                'attributes' => [
                                                    'status'  => '',
                                                    'message' => '',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'user_with_profiles_and_status_and_sub_sub' => [
                $provider->getUserStructure([
                    '[profiles:userProfile]',
                    '[profiles].profileStatus',
                    '[profiles].profileStatus.[lecturers:user]',
                ]),
                [
                    'id'            => '',
                    'type'          => '',
                    'attributes'    => [
                        'uid'       => '',
                        'firstName' => '',
                        'lastName'  => '',
                        'fullName'  => '',
                        'email'     => '',
                        'avatar'    => '',
                        'photoUrl'  => '',
                    ],
                    'relationships' => [
                        'profiles' => [
                            'data' => [
                                [
                                    'type'          => '',
                                    'id'            => '',
                                    'attributes'    => [
                                        'type'   => '',
                                        'degree' => '',
                                    ],
                                    'relationships' => [
                                        'profileStatus' => [
                                            'data' => [
                                                'type'          => '',
                                                'id'            => '',
                                                'attributes'    => [
                                                    'status'  => '',
                                                    'message' => '',
                                                ],
                                                'relationships' => [
                                                    'lecturers' => [
                                                        'data' => [
                                                            [
                                                                'id'         => '',
                                                                'type'       => '',
                                                                'attributes' => [
                                                                    'uid'       => '',
                                                                    'firstName' => '',
                                                                    'lastName'  => '',
                                                                    'fullName'  => '',
                                                                    'email'     => '',
                                                                    'avatar'    => '',
                                                                    'photoUrl'  => '',
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function assertStructure(array $structure, array $responseData): self
    {
        foreach ($structure as $key => $value) {
            if (is_array($value) && $key === '*') {
                PHPUnit::assertIsArray($responseData);

                foreach ($responseData as $responseDataItem) {
                    $this->assertStructure($structure['*'], $responseDataItem);
                }
            } elseif (is_array($value)) {
                PHPUnit::assertArrayHasKey($key, $responseData);

                $this->assertStructure($structure[$key], $responseData[$key]);
            } else {
                PHPUnit::assertArrayHasKey($value, $responseData);
            }
        }

        return $this;
    }
}
