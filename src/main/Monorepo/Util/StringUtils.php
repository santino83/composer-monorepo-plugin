<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 21/07/19
 * Time: 19.09
 */

namespace Monorepo\Util;


use Jawira\CaseConverter\Convert;

class StringUtils
{

    public static function toDirectoryPath($string)
    {
        return implode(DIRECTORY_SEPARATOR, array_map(StringUtils::class.'::toKebab', array_map('strtolower', preg_split('@[\\\\/]@i', $string))));
    }

    public static function toNamespace($string)
    {
        return implode('\\', array_map(StringUtils::class.'::toPascal', preg_split('@[\\\\/]@i', $string)));
    }

    public static function toPackageName($string)
    {
        return implode('/', array_map(StringUtils::class.'::toKebab', preg_split('@[\\\\/]@i', $string)));
    }

    public static function toCamel($string)
    {
        return self::useConvert($string, 'toCamel');
    }

    public static function toPascal($string)
    {
        return self::useConvert($string, 'toPascal');
    }

    public static function toSnake($string)
    {
        return self::useConvert($string, 'toSnake');
    }

    public static function toAda($string)
    {
        return self::useConvert($string, 'toAda');
    }

    public static function toMacro($string)
    {
        return self::useConvert($string, 'toMacro');
    }

    public static function toKebab($string)
    {
        return self::useConvert($string, 'toKebab');
    }

    public static function toTrain($string)
    {
        return self::useConvert($string, 'toTrain');
    }

    public static function toCobol($string)
    {
        return self::useConvert($string, 'toCobol');
    }

    public static function toLower($string)
    {
        return self::useConvert($string, 'toLower');
    }

    public static function toUpper($string)
    {
        return self::useConvert($string, 'toUpper');
    }

    public static function toTitle($string)
    {
        return self::useConvert($string, 'toTitle');
    }

    public static function toSentence($string)
    {
        return self::useConvert($string, 'toSentence');
    }

    public static function getWords($string)
    {
        return self::useConvert($string, 'toArray');
    }

    private static function useConvert($string, $targetMethod)
    {
        try {
            $convert = new Convert($string);
            return $convert->{$targetMethod}();
        }catch (\Exception $ex){
            return $string;
        }
    }

}