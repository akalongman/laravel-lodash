<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use Longman\LaravelLodash\Support\Declensions;
use Tests\Unit\TestCase;

class DeclensionsTest extends TestCase
{
    /**
     * @dataProvider dataForDeclension3()
     * @test
     */
    public function turn_words_declension3(string $word, string $turned): void
    {
        $this->assertSame($turned, Declensions::applyDeclension($word, Declensions::DECLENSION_3));
    }

    /**
     * @dataProvider dataForDeclension4()
     * @test
     */
    public function turn_words_declension4(string $word, string $turned): void
    {
        $this->assertSame($turned, Declensions::applyDeclension($word, Declensions::DECLENSION_4));
    }

    /**
     * Data provider
     *
     * @see turn_words_declension3()
     */
    public function dataForDeclension3(): array
    {
        return [
            'ად' => [
                'word'   => 'მოჰამმად',
                'turned' => 'მოჰამმადს',
            ],
            'ან' => [
                'word'   => 'მარქარიან',
                'turned' => 'მარქარიანს',
            ],
            'დე' => [
                'word'   => 'მენაბდე',
                'turned' => 'მენაბდეს',
            ],
            'დი' => [
                'word'   => 'მაჰდი',
                'turned' => 'მაჰდის',
            ],
            'ვა' => [
                'word'   => 'სიჭინავა',
                'turned' => 'სიჭინავას',
            ],
            'ვი' => [
                'word'   => 'ბარნოვი',
                'turned' => 'ბარნოვს',
            ],
            'ია' => [
                'word'   => 'დანელია',
                'turned' => 'დანელიას',
            ],
            'კა' => [
                'word'   => 'ოდინაკა',
                'turned' => 'ოდინაკას',
            ],
            'კი' => [
                'word'   => 'კანდელაკი',
                'turned' => 'კანდელაკს',
            ],
            'კო' => [
                'word'   => 'ბოიჩენკო',
                'turned' => 'ბოიჩენკოს',
            ],
            'ლი' => [
                'word'   => 'ქევხიშვილი',
                'turned' => 'ქევხიშვილს',
            ],
            'ნი' => [
                'word'   => 'გელბახიანი',
                'turned' => 'გელბახიანს',
            ],
            'რი' => [
                'word'   => 'თაბაგარი',
                'turned' => 'თაბაგარს',
            ],
            'სი' => [
                'word'   => 'მეღვინეთუხუცესი',
                'turned' => 'მეღვინეთუხუცესს',
            ],
            'ტი' => [
                'word'   => 'ღლონტი',
                'turned' => 'ღლონტს',
            ],
            'უა' => [
                'word'   => 'თოდუა',
                'turned' => 'თოდუას',
            ],
            'ში' => [
                'word'   => 'ტუღუში',
                'turned' => 'ტუღუშს',
            ],
            'ცი' => [
                'word'   => 'ახალკაცი',
                'turned' => 'ახალკაცს',
            ],
            'ძე' => [
                'word'   => 'კიკაბიძე',
                'turned' => 'კიკაბიძეს',
            ],
            'ხი' => [
                'word'   => 'მესხი',
                'turned' => 'მესხს',
            ],
            '*'  => [
                'word'   => 'ბჰაილალბჰაი',
                'turned' => 'ბჰაილალბჰაის',
            ],
        ];
    }

    /**
     * Data provider
     *
     * @see turn_words_declension4()
     */
    public function dataForDeclension4(): array
    {
        return [
            'ად' => [
                'word'   => 'მოჰამმად',
                'turned' => 'მოჰამმადის',
            ],
            'ან' => [
                'word'   => 'მარქარიან',
                'turned' => 'მარქარიანის',
            ],
            'დე' => [
                'word'   => 'მენაბდე',
                'turned' => 'მენაბდის',
            ],
            'დი' => [
                'word'   => 'მაჰდი',
                'turned' => 'მაჰდის',
            ],
            'ვა' => [
                'word'   => 'სიჭინავა',
                'turned' => 'სიჭინავას',
            ],
            'ვი' => [
                'word'   => 'ბარნოვი',
                'turned' => 'ბარნოვის',
            ],
            'ია' => [
                'word'   => 'დანელია',
                'turned' => 'დანელიას',
            ],
            'კა' => [
                'word'   => 'ოდინაკა',
                'turned' => 'ოდინაკას',
            ],
            'კი' => [
                'word'   => 'კანდელაკი',
                'turned' => 'კანდელაკის',
            ],
            'კო' => [
                'word'   => 'ბოიჩენკო',
                'turned' => 'ბოიჩენკოს',
            ],
            'ლი' => [
                'word'   => 'ქევხიშვილი',
                'turned' => 'ქევხიშვილის',
            ],
            'ნი' => [
                'word'   => 'გელბახიანი',
                'turned' => 'გელბახიანის',
            ],
            'რი' => [
                'word'   => 'თაბაგარი',
                'turned' => 'თაბაგარის',
            ],
            'სი' => [
                'word'   => 'მეღვინეთუხუცესი',
                'turned' => 'მეღვინეთუხუცესის',
            ],
            'ტი' => [
                'word'   => 'ღლონტი',
                'turned' => 'ღლონტის',
            ],
            'უა' => [
                'word'   => 'თოდუა',
                'turned' => 'თოდუას',
            ],
            'ში' => [
                'word'   => 'ტუღუში',
                'turned' => 'ტუღუშის',
            ],
            'ცი' => [
                'word'   => 'ახალკაცი',
                'turned' => 'ახალკაცის',
            ],
            'ძე' => [
                'word'   => 'კიკაბიძე',
                'turned' => 'კიკაბიძის',
            ],
            'ხი' => [
                'word'   => 'მესხი',
                'turned' => 'მესხის',
            ],
            '*'  => [
                'word'   => 'ბჰაილალბჰაი',
                'turned' => 'ბჰაილალბჰაის',
            ],
        ];
    }
}
