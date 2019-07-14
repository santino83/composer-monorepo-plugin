<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 14/07/19
 * Time: 1.40
 */

namespace Monorepo\Loader;


use Monorepo\Composer\Util\Filesystem;

class MonorepoJsonLoaderTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var MonorepoJsonLoader
     */
    private $loader;

    /**
     * @var string
     */
    private $fixtureDir;

    /**
     * @var Filesystem
     */
    private $fs;

    protected function setUp()
    {
        $this->loader = new MonorepoJsonLoader();
        $this->fs = new Filesystem();
        $this->fixtureDir = $this->fs->path(dirname(__DIR__),'..','_fixtures');
    }

    public function testLoadSimple()
    {
        $c1 = $this->fs->path($this->fixtureDir,'example-simple','bar','monorepo.json');
        $c2 = $this->fs->path($this->fixtureDir,'example-simple','foo','monorepo.json');

        $this->assertNotEmpty($this->loader->fromFile($c1));
        $this->assertNotEmpty($this->loader->fromFile($c2));
    }

    public function testLoadNoDev()
    {
        $c1 = $this->fs->path($this->fixtureDir,'example-nodev','bar','monorepo.json');
        $c2 = $this->fs->path($this->fixtureDir,'example-nodev','foo','monorepo.json');

        $this->assertNotEmpty($this->loader->fromFile($c1));
        $this->assertNotEmpty($this->loader->fromFile($c2));
    }

    public function testLoadSimpleFromJson()
    {
        $c1 = file_get_contents($this->fs->path($this->fixtureDir,'example-simple','bar','monorepo.json'));
        $c2 = file_get_contents($this->fs->path($this->fixtureDir,'example-simple','foo','monorepo.json'));

        $this->assertNotEmpty($this->loader->fromJson($c1));
        $this->assertNotEmpty($this->loader->fromJson($c2));
    }

    public function testLoadNoDevFromJson()
    {
        $c1 = file_get_contents($this->fs->path($this->fixtureDir,'example-nodev','bar','monorepo.json'));
        $c2 = file_get_contents($this->fs->path($this->fixtureDir,'example-nodev','foo','monorepo.json'));

        $this->assertNotEmpty($this->loader->fromJson($c1));
        $this->assertNotEmpty($this->loader->fromJson($c2));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testLoadInvalidFromFile()
    {
        $c = $this->fs->path($this->fixtureDir,'another-schema','another-schema.json');
        $this->loader->fromFile($c);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testLoadInvalidFromJson()
    {
        $c = file_get_contents($this->fs->path($this->fixtureDir,'another-schema','another-schema.json'));
        $this->loader->fromJson($c);
    }

}