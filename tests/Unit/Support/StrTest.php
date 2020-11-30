<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use Longman\LaravelLodash\Support\Str;
use stdClass;
use Tests\Unit\TestCase;

use function mb_strlen;

class StrTest extends TestCase
{
    /** @test */
    public function add_zeros(): void
    {
        $string = '12345678';

        $this->assertSame('0012345678', Str::addZeros($string, 10, 'left'));
        $this->assertSame('1234567800', Str::addZeros($string, 10, 'right'));
    }

    /** @test */
    public function format_balance(): void
    {
        $int = 12345;

        $this->assertSame('123.45', Str::formatBalance($int, 2));
        $this->assertSame('123.450', Str::formatBalance($int, 3));
    }

    /** @test */
    public function snake_case_to_camel_case(): void
    {
        $string = 'Lorem_ipsum_dolores';
        $this->assertSame('LoremIpsumDolores', Str::snakeCaseToCamelCase($string));
    }

    /** @test */
    public function camel_case_to_snake_case(): void
    {
        $string = 'LoremIpsumDolores';
        $this->assertSame('lorem_ipsum_dolores', Str::camelCaseToSnakeCase($string));

        $string = 'არჩევანისგარემოსუზრუნველყოფისსისტემა';
        $this->assertSame('არჩევანისგარემოსუზრუნველყოფისსისტემა', Str::camelCaseToSnakeCase($string));
    }

    /** @test */
    public function convert_spaces_to_dashes(): void
    {
        $string = 'Lorem Ipsum Dolores';
        $this->assertSame('Lorem-Ipsum-Dolores', Str::convertSpacesToDashes($string));

        $string = 'არჩევანის გარემოს უზრუნველყოფის სისტემა';
        $this->assertSame('არჩევანის-გარემოს-უზრუნველყოფის-სისტემა', Str::convertSpacesToDashes($string));
    }

    /** @test */
    public function limit_middle(): void
    {
        $string = 'არჩევანის გარემოს უზრუნველყოფის სისტემა';

        $this->assertSame('არჩევანის ...ის სისტემა', Str::limitMiddle($string, 20, '...'));
        $this->assertSame(23, mb_strlen(Str::limitMiddle($string, 20, '...')));
    }

    /** @test */
    public function hash(): void
    {
        $data = ['aa' => 1, 'bb' => 2, 'cc' => 3];
        $this->assertSame('899a999da95e9f021fc63c6af006933fd4dc3aa1', Str::hash($data));

        $data = new stdClass();
        $data->aaa = 1;
        $data->bbb = 2;
        $data->ccc = 3;
        $this->assertSame('41d162b72eab4e7cfb6bb853d651fbaa2ae0573b', Str::hash($data));

        $data = null;
        $this->assertSame('eef19c54306daa69eda49c0272623bdb5e2b341f', Str::hash($data));
    }

    /** @test */
    public function to_dot_notation(): void
    {
        $string = 'data[first][]';
        $this->assertSame('data.first[]', Str::toDotNotation($string));

        $string = 'data[first][second]';
        $this->assertSame('data.first.second', Str::toDotNotation($string));

        $string = 'data[first][second]third';
        $this->assertSame('data.first.secondthird', Str::toDotNotation($string));

        $string = 'data[first][second][0]';
        $this->assertSame('data.first.second.0', Str::toDotNotation($string));
    }

    /** @test */
    public function convert_to_utf8(): void
    {
        $data = 'hello žš, გამარჯობა';
        $this->assertSame('hello žš, გამარჯობა', Str::convertToUtf8($data));

        $data = 'Hírek';
        $this->assertSame('Hírek', Str::convertToUtf8($data));

        $data = 'H�rek';
        $this->assertSame('H�rek', Str::convertToUtf8($data));

        $data = "FÃÂ©dération Camerounaise de Football\n";
        $this->assertSame('FÃÂ©dération Camerounaise de Football', Str::convertToUtf8($data));
    }
}
