<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 15/07/19
 * Time: 13.30
 */

namespace Monorepo;


use Monorepo\Composer\Util\Filesystem;
use Monorepo\Loader\MonorepoLoader;

class ConsoleTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var MonorepoLoader
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
        $this->loader = new MonorepoLoader();
        $this->fs = new Filesystem();
        $this->fixtureDir = $this->fs->path(dirname(__FILE__),'..','_fixtures');
    }

    public function testBuild()
    {
        $path = $this->fs->path($this->fixtureDir, 'example-tree');
        $context = ContextBuilder::create()->build($path);

        $console = new Console($this->loader);
        $console->build($context);

    }
/*
    public function testInitOK()
    {
        $path = $this->fs->path($this->fixtureDir, 'example-init');
        $context = ContextBuilder::create()->build($path);

        $console = new Console($this->loader);
        $console->init($context);

        $this->assertTrue(file_exists($this->fs->path($path, 'monorepo.json')));
        unlink($this->fs->path($path, 'monorepo.json'));
    }
*/
    /**
     * @expectedException \RuntimeException
     */
/*    public function testInitNoComposer()
    {
        $path = $this->fs->path($this->fixtureDir, 'example-init-invalid-composer');

        $context = ContextBuilder::create()->build($path);

        $console = new Console($this->loader);
        $console->init($context);
    }
*/
    /**
     * @expectedException \RuntimeException
     */
/*    public function testAlreadyInitialized()
    {
        $path = $this->fs->path($this->fixtureDir, 'example-init-invalid-monorepo');

        $context = ContextBuilder::create()->build($path);

        $console = new Console($this->loader);
        $console->init($context);
    }
*/
}