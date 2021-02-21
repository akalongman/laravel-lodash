<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use Longman\LaravelLodash\Support\Declensions;
use Tests\Unit\TestCase;

class DeclensionsTest extends TestCase
{
    /**
     * @dataProvider dataForDeclensions()
     * @test
     */
    public function turn_words_declensions(string $word, array $declensions): void
    {
        foreach ($declensions as $declension => $turned) {
            $this->assertSame($turned, Declensions::applyDeclension($word, $declension));
        }
    }

    /**
     * Data provider
     *
     * @see turn_words_declensions()
     */
    public function dataForDeclensions(): array
    {
        return [
            'მოჰამმად'        => [
                'word'   => 'მოჰამმად',
                'turned' => [
                    Declensions::DECLENSION_3 => 'მოჰამმადს',
                    Declensions::DECLENSION_4 => 'მოჰამმადის',
                ],
            ],
            'მარქარიან'       => [
                'word'   => 'მარქარიან',
                'turned' => [
                    Declensions::DECLENSION_3 => 'მარქარიანს',
                    Declensions::DECLENSION_4 => 'მარქარიანის',
                ],
            ],
            'მენაბდე'         => [
                'word'   => 'მენაბდე',
                'turned' => [
                    Declensions::DECLENSION_3 => 'მენაბდეს',
                    Declensions::DECLENSION_4 => 'მენაბდის',
                ],
            ],
            'მაჰდი'           => [
                'word'   => 'მაჰდი',
                'turned' => [
                    Declensions::DECLENSION_3 => 'მაჰდის',
                    Declensions::DECLENSION_4 => 'მაჰდის',
                ],
            ],
            'სიჭინავა'        => [
                'word'   => 'სიჭინავა',
                'turned' => [
                    Declensions::DECLENSION_3 => 'სიჭინავას',
                    Declensions::DECLENSION_4 => 'სიჭინავას',
                ],
            ],
            'ბარნოვი'         => [
                'word'   => 'ბარნოვი',
                'turned' => [
                    Declensions::DECLENSION_3 => 'ბარნოვს',
                    Declensions::DECLENSION_4 => 'ბარნოვის',
                ],
            ],
            'დანელია'         => [
                'word'   => 'დანელია',
                'turned' => [
                    Declensions::DECLENSION_3 => 'დანელიას',
                    Declensions::DECLENSION_4 => 'დანელიას',
                ],
            ],
            'ოდინაკა'         => [
                'word'   => 'ოდინაკა',
                'turned' => [
                    Declensions::DECLENSION_3 => 'ოდინაკას',
                    Declensions::DECLENSION_4 => 'ოდინაკას',
                ],
            ],
            'კანდელაკი'       => [
                'word'   => 'კანდელაკი',
                'turned' => [
                    Declensions::DECLENSION_3 => 'კანდელაკს',
                    Declensions::DECLENSION_4 => 'კანდელაკის',
                ],
            ],
            'ბოიჩენკო'        => [
                'word'   => 'ბოიჩენკო',
                'turned' => [
                    Declensions::DECLENSION_3 => 'ბოიჩენკოს',
                    Declensions::DECLENSION_4 => 'ბოიჩენკოს',
                ],
            ],
            'ქევხიშვილი'      => [
                'word'   => 'ქევხიშვილი',
                'turned' => [
                    Declensions::DECLENSION_3 => 'ქევხიშვილს',
                    Declensions::DECLENSION_4 => 'ქევხიშვილის',
                ],
            ],
            'გელბახიანი'      => [
                'word'   => 'გელბახიანი',
                'turned' => [
                    Declensions::DECLENSION_3 => 'გელბახიანს',
                    Declensions::DECLENSION_4 => 'გელბახიანის',
                ],
            ],
            'თაბაგარი'        => [
                'word'   => 'თაბაგარი',
                'turned' => [
                    Declensions::DECLENSION_3 => 'თაბაგარს',
                    Declensions::DECLENSION_4 => 'თაბაგარის',
                ],
            ],
            'მეღვინეთუხუცესი' => [
                'word'   => 'მეღვინეთუხუცესი',
                'turned' => [
                    Declensions::DECLENSION_3 => 'მეღვინეთუხუცესს',
                    Declensions::DECLENSION_4 => 'მეღვინეთუხუცესის',
                ],
            ],
            'ღლონტი'          => [
                'word'   => 'ღლონტი',
                'turned' => [
                    Declensions::DECLENSION_3 => 'ღლონტს',
                    Declensions::DECLENSION_4 => 'ღლონტის',
                ],
            ],
            'თოდუა'           => [
                'word'   => 'თოდუა',
                'turned' => [
                    Declensions::DECLENSION_3 => 'თოდუას',
                    Declensions::DECLENSION_4 => 'თოდუას',
                ],
            ],
            'ტუღუში'          => [
                'word'   => 'ტუღუში',
                'turned' => [
                    Declensions::DECLENSION_3 => 'ტუღუშს',
                    Declensions::DECLENSION_4 => 'ტუღუშის',
                ],
            ],
            'ახალკაცი'        => [
                'word'   => 'ახალკაცი',
                'turned' => [
                    Declensions::DECLENSION_3 => 'ახალკაცს',
                    Declensions::DECLENSION_4 => 'ახალკაცის',
                ],
            ],
            'კიკაბიძე'        => [
                'word'   => 'კიკაბიძე',
                'turned' => [
                    Declensions::DECLENSION_3 => 'კიკაბიძეს',
                    Declensions::DECLENSION_4 => 'კიკაბიძის',
                ],
            ],
            'მესხი'           => [
                'word'   => 'მესხი',
                'turned' => [
                    Declensions::DECLENSION_3 => 'მესხს',
                    Declensions::DECLENSION_4 => 'მესხის',
                ],
            ],
            'ბჰაილალბჰაი'     => [
                'word'   => 'ბჰაილალბჰაი',
                'turned' => [
                    Declensions::DECLENSION_3 => 'ბჰაილალბჰაის',
                    Declensions::DECLENSION_4 => 'ბჰაილალბჰაის',
                ],
            ],
            'ადმინისტრაცია'   => [
                'word'   => 'ადმინისტრაცია',
                'turned' => [
                    Declensions::DECLENSION_3 => 'ადმინისტრაციას',
                    Declensions::DECLENSION_4 => 'ადმინისტრაციის',
                ],
            ],
            'თანამშრომელი'    => [
                'word'   => 'თანამშრომელი',
                'turned' => [
                    Declensions::DECLENSION_3 => 'თანამშრომელს',
                    Declensions::DECLENSION_4 => 'თანამშრომლის',
                ],
            ],
            'თანაშემწე'       => [
                'word'   => 'თანაშემწე',
                'turned' => [
                    Declensions::DECLENSION_3 => 'თანაშემწეს',
                    Declensions::DECLENSION_4 => 'თანაშემწის',
                ],
            ],
            'ინჟინერი'        => [
                'word'   => 'ინჟინერი',
                'turned' => [
                    Declensions::DECLENSION_3 => 'ინჟინერს',
                    Declensions::DECLENSION_4 => 'ინჟინრის',
                ],
            ],
            'ოფიცერი'         => [
                'word'   => 'ოფიცერი',
                'turned' => [
                    Declensions::DECLENSION_3 => 'ოფიცერს',
                    Declensions::DECLENSION_4 => 'ოფიცრის',
                ],
            ],
            'ბიბლიოთეკა'      => [
                'word'   => 'ბიბლიოთეკა',
                'turned' => [
                    Declensions::DECLENSION_3 => 'ბიბლიოთეკას',
                    Declensions::DECLENSION_4 => 'ბიბლიოთეკის',
                ],
            ],
            'სკოლა'           => [
                'word'   => 'სკოლა',
                'turned' => [
                    Declensions::DECLENSION_3 => 'სკოლას',
                    Declensions::DECLENSION_4 => 'სკოლის',
                ],
            ],
            'მოადგილე'        => [
                'word'   => 'მოადგილე',
                'turned' => [
                    Declensions::DECLENSION_3 => 'მოადგილეს',
                    Declensions::DECLENSION_4 => 'მოადგილის',
                ],
            ],
            'გამგე'           => [
                'word'   => 'გამგე',
                'turned' => [
                    Declensions::DECLENSION_3 => 'გამგეს',
                    Declensions::DECLENSION_4 => 'გამგის',
                ],
            ],
            'დამლაგებელი'     => [
                'word'   => 'დამლაგებელი',
                'turned' => [
                    Declensions::DECLENSION_3 => 'დამლაგებელს',
                    Declensions::DECLENSION_4 => 'დამლაგებლის',
                ],
            ],
            'ბუღალტერი'       => [
                'word'   => 'ბუღალტერი',
                'turned' => [
                    Declensions::DECLENSION_3 => 'ბუღალტერს',
                    Declensions::DECLENSION_4 => 'ბუღალტრის',
                ],
            ],
        ];
    }
}
