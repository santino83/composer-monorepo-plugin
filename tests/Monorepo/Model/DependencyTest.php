<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 14/07/19
 * Time: 19.11
 */

namespace Monorepo\Model;


class DependencyTest extends \PHPUnit_Framework_TestCase
{

    public function testAccessFunctions()
    {
        $dep = new Dependency();

        $this->assertArrayNotHasKey('foo', $dep);
        $dep["foo"] = "075";
        $this->assertArrayHasKey('foo', $dep);
    }

    public function testForEachFunctions()
    {
        $dep = new Dependency();
        $dep['foo'] = '1';
        $dep['bar'] = '2';

        foreach ($dep as $packageName => $version){
            $this->assertTrue(in_array($packageName, ['foo','bar']));
            switch ($packageName){
                case 'foo':
                    $this->assertEquals('1', $version);
                    break;
                case 'bar':
                    $this->assertEquals('2', $version);
                    break;
                default:
                    $this->fail('Invalid couple '.$packageName.'/'.$version);
            }
        }

    }

}