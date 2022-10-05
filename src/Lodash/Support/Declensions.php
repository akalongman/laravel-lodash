<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Support;

use InvalidArgumentException;

use function in_array;
use function is_null;

abstract class Declensions
{
    /** @link https://ka.wikipedia.org/wiki/%E1%83%91%E1%83%A0%E1%83%A3%E1%83%9C%E1%83%94%E1%83%91%E1%83%90 */
    public const DECLENSION_1 = 1; // სახელობითი
    public const DECLENSION_2 = 2; // მოთხრობითი
    public const DECLENSION_3 = 3; // მიცემითი
    public const DECLENSION_4 = 4; // ნათესაობითი
    public const DECLENSION_5 = 5; // მოქმედებითი
    public const DECLENSION_6 = 6; // ვითარებითი
    public const DECLENSION_7 = 7; // წოდებითი

    private static array $declensionRules = [
        self::DECLENSION_1 => [ // სახელობითი
            '*' => 'ი',
        ],
        self::DECLENSION_2 => [ // მოთხრობითი
            '*' => 'მა',
        ],
        self::DECLENSION_3 => [ // მიცემითი
            'ადმინისტრაცია' => 'ადმინისტრაციას',
            'თანამშრომელი'  => 'თანამშრომელს',
            'ინჟინერი'      => 'ინჟინერს',
            'ოფიცერი'       => 'ოფიცერს',
            'ბიბლიოთეკა'    => 'ბიბლიოთეკას',
            'დამლაგებელი'   => 'დამლაგებელს',
            'ბუღალტერი'     => 'ბუღალტერს',
            'ად'            => 'ადს',
            'ან'            => 'ანს',
            'ამ'            => 'ამს',
            'ბა'            => 'ბას',
            'გე'            => 'გეს',
            'დე'            => 'დეს',
            'დი'            => 'დის',
            'ვა'            => 'ვას',
            'ვი'            => 'ვს',
            'ია'            => 'იას',
            'კა'            => 'კას',
            'კი'            => 'კს',
            'კო'            => 'კოს',
            'ლა'            => 'ლას',
            'ლი'            => 'ლს',
            'ლე'            => 'ლეს',
            'ნი'            => 'ნს',
            'რი'            => 'რს',
            'სი'            => 'სს',
            'ტი'            => 'ტს',
            'უა'            => 'უას',
            'ში'            => 'შს',
            'ცი'            => 'ცს',
            'ძე'            => 'ძეს',
            'წე'            => 'წეს',
            'ხი'            => 'ხს',
            '*'             => 'ს',
        ],
        self::DECLENSION_4 => [ // ნათესაობითი
            'ადმინისტრაცია' => 'ადმინისტრაციის',
            'თანამშრომელი'  => 'თანამშრომლის',
            'ინჟინერი'      => 'ინჟინრის',
            'ოფიცერი'       => 'ოფიცრის',
            'ბიბლიოთეკა'    => 'ბიბლიოთეკის',
            'დამლაგებელი'   => 'დამლაგებლის',
            'ბუღალტერი'     => 'ბუღალტრის',
            'ად'            => 'ადის',
            'ან'            => 'ანის',
            'ამ'            => 'ამის',
            'ბა'            => 'ბის',
            'გე'            => 'გის',
            'დე'            => 'დის',
            'დი'            => 'დის',
            'ვა'            => 'ვას',
            'ვი'            => 'ვის',
            'ია'            => 'იას',
            'კა'            => 'კას',
            'კი'            => 'კის',
            'კო'            => 'კოს',
            'ლა'            => 'ლის',
            'ლი'            => 'ლის',
            'ლე'            => 'ლის',
            'ნი'            => 'ნის',
            'რი'            => 'რის',
            'სი'            => 'სის',
            'ტი'            => 'ტის',
            'უა'            => 'უას',
            'ში'            => 'შის',
            'ცი'            => 'ცის',
            'ძე'            => 'ძის',
            'წე'            => 'წის',
            'ხი'            => 'ხის',
            '*'             => 'ს', // ის
        ],
        self::DECLENSION_5 => [ // მოქმედებითი
            '*' => 'ით',
        ],
        self::DECLENSION_6 => [ // ვითარებითი
            '*' => 'ად',
        ],
        self::DECLENSION_7 => [ // წოდებითი
            '*' => 'ო',
        ],
    ];

    public static function getAvailableDeclensions(): array
    {
        return [self::DECLENSION_1, self::DECLENSION_2, self::DECLENSION_3, self::DECLENSION_4, self::DECLENSION_5, self::DECLENSION_6, self::DECLENSION_7];
    }

    public static function applyDeclension(string $word, int $declension): string
    {
        if (! in_array($declension, self::getAvailableDeclensions(), true)) {
            throw new InvalidArgumentException('Declension "' . $declension . '" is invalid');
        }

        $rules = self::$declensionRules[$declension];
        $turnedWord = null;
        foreach ($rules as $suffix1 => $suffix2) {
            if (Str::substr($word, -Str::length($suffix1)) === $suffix1) {
                $turnedWord = Str::substr($word, 0, -Str::length($suffix1)) . $suffix2;
                break;
            }
        }

        if (is_null($turnedWord)) {
            $turnedWord = $word . $rules['*'];
        }

        return $turnedWord;
    }
}
