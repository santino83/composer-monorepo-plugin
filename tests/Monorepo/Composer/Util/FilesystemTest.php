<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 13/07/19
 * Time: 23.18
 */

namespace Monorepo\Composer\Util;

class FilesystemTest extends \PHPUnit_Framework_TestCase
{
    
    public function testJoinPaths()
    {
        $fs = new Filesystem();
        
        $exp1 = 'a/b';
        $exp2 = 'a/b/c';
        $exp3 = 'a/b/c/d';

        $this->assertEquals($exp1,$fs->path('a','b'));
        $this->assertEquals($exp1,$fs->path('a/','b'));
        $this->assertEquals($exp1,$fs->path('a','/b'));
        $this->assertEquals($exp1,$fs->path('a/','/b'));

        $this->assertEquals($exp2,$fs->path('a','b','c'));
        $this->assertEquals($exp2,$fs->path('a','b','/c'));
        $this->assertEquals($exp2,$fs->path('a/b','c'));
        $this->assertEquals($exp2,$fs->path('a/b/','c'));
        $this->assertEquals($exp2,$fs->path('a/b/','/c'));

        $this->assertEquals($exp3,$fs->path('a','b','c','d'));
        $this->assertEquals($exp3,$fs->path('a/b/c','d'));
        $this->assertEquals($exp3,$fs->path('a/b/c/','d'));
        $this->assertEquals($exp3,$fs->path('a/b/c','/d'));
        $this->assertEquals($exp3,$fs->path('a/b/c/','/d'));
    }

}