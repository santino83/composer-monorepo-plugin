<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 21/07/19
 * Time: 19.48
 */

namespace Monorepo\Util;


use PHPUnit\Framework\TestCase;

class StringUtilsTest extends TestCase
{

    public function testToPascal()
    {
        $this->assertEquals('Another', StringUtils::toPascal('another'));
        $this->assertEquals('AnotherOne', StringUtils::toPascal('another-one'));
        $this->assertEquals('AnotherOne', StringUtils::toPascal('another_one'));
        $this->assertEquals('AnotherOne', StringUtils::toPascal('another one'));
    }

    public function testToNamespace()
    {
        $this->assertEquals('AnotherOne', StringUtils::toNamespace('another-one'));
        $this->assertEquals('Example\\AnotherOne', StringUtils::toNamespace('example/another-one'));
        $this->assertEquals('Example\\AnotherOne', StringUtils::toNamespace('example\\another-one'));
    }

    public function testToDirectory()
    {
        $this->assertEquals('another-one', StringUtils::toDirectoryPath('another-one'));
        $this->assertEquals('example/another-one', StringUtils::toDirectoryPath('example/another one'));
    }

    public function testToPackageName()
    {
        $this->assertEquals('buzz', StringUtils::toPackageName('Buzz'));
        $this->assertEquals('test/buzz', StringUtils::toPackageName('Test\\Buzz'));
        $this->assertEquals('buzz-old', StringUtils::toPackageName('BuzzOld'));
        $this->assertEquals('test/buzz-old', StringUtils::toPackageName('Test\\BuzzOld'));
        $this->assertEquals('test/buzz', StringUtils::toPackageName('test/buzz'));
    }

}