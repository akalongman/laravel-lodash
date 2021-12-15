<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Support;

use ForceUTF8\Encoding;
use Illuminate\Support\Str as BaseStr;
use RuntimeException;

use function class_exists;
use function html_entity_decode;
use function is_array;
use function is_null;
use function is_object;
use function max;
use function mb_substr;
use function number_format;
use function preg_replace;
use function serialize;
use function sha1;
use function sort;
use function str_repeat;
use function str_replace;
use function stripslashes;
use function strtolower;
use function trim;
use function ucwords;

use const ENT_QUOTES;

class Str extends BaseStr
{
    public static function addZeros(string $value, int $finalLength = 2, string $dir = 'left'): string
    {
        $length = self::length($value);
        if ($length >= $finalLength) {
            return $value;
        }
        $diff = $finalLength - $length;
        $value = $dir === 'left' ? str_repeat('0', $diff) . $value : $value . str_repeat('0', $diff);

        return $value;
    }

    public static function formatBalance(?int $amount = 0, int $d = 2): string
    {
        //$amount = str_replace([',', ' '], ['.', ''], $amount);
        $amount = (float) $amount;
        $amount /= 100;
        $amount = number_format($amount, $d, '.', '');

        return $amount;
    }

    public static function snakeCaseToCamelCase(string $string): string
    {
        $str = str_replace('_', '', ucwords($string, '_'));

        return $str;
    }

    public static function camelCaseToSnakeCase(string $string): string
    {
        $string = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $string));

        return $string;
    }

    public static function convertSpacesToDashes(string $string): string
    {
        $string = str_replace(' ', '-', $string);

        return $string;
    }

    public static function substrReplaceUnicode(string $string, string $replacement, int $start, ?int $length = null): string
    {
        $strLength = self::length($string);

        if ($start < 0) {
            $start = max(0, $strLength + $start);
        } elseif ($start > $strLength) {
            $start = $strLength;
        }

        if ($length < 0) {
            $length = max(0, $strLength - $start + $length);
        } elseif ((is_null($length) === true) || ($length > $strLength)) {
            $length = $strLength;
        }

        if (($start + $length) > $strLength) {
            $length = $strLength - $start;
        }

        return mb_substr($string, 0, $start) . $replacement . mb_substr($string, $start + $length, $strLength - $start - $length);
    }

    public static function limitMiddle(string $value, int $limit = 100, string $separator = '...'): string
    {
        $length = self::length($value);

        if ($length <= $limit) {
            return $value;
        }

        return self::substrReplaceUnicode($value, $separator, $limit / 2, $length - $limit);
    }

    public static function toDotNotation(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/\[(.+)\]/U', '.$1', $value);

        return $value;
    }

    /**
     * @param mixed $data
     * @return string
     */
    public static function hash($data): string
    {
        if (is_array($data)) {
            sort($data);
            $data = serialize($data);
        }

        if (is_object($data)) {
            $data = serialize($data);
        }

        if (is_null($data)) {
            $data = 'NULL';
        }

        return sha1((string) $data);
    }

    public static function convertToUtf8(string $string): string
    {
        if (! class_exists(Encoding::class)) {
            throw new RuntimeException('To use this method, package "neitanod/forceutf8" should be installed!');
        }

        /** @link https://github.com/neitanod/forceutf8 */
        $string = Encoding::toUTF8($string);

        $string = stripslashes(trim($string));

        $string = html_entity_decode($string, ENT_QUOTES, 'UTF-8');

        return $string;
    }
}
