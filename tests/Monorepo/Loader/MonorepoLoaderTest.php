<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 14/07/19
 * Time: 1.40
 */

namespace Monorepo\Loader;


use Monorepo\Composer\Util\Filesystem;

class MonorepoLoaderTest extends \PHPUnit_Framework_TestCase
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

    public function testLoadRootFromComposer()
    {
        $monorepo = $this->loader->fromComposer($this->fs->path($this->fixtureDir, 'resources','composer-test.json'), true);

        $this->assertTrue($monorepo->isRoot());
        $this->assertEquals('circlecrm/vauth', $monorepo->getName());

        $this->assertTrue($monorepo->getRequire()->has('symfony/symfony'));
        $this->assertTrue($monorepo->getRequire()->has('php'));
        $this->assertTrue($monorepo->getRequire()->has('fortawesome/font-awesome'));

        $this->assertTrue($monorepo->getRequireDev()->has('sensio/generator-bundle'));

        $this->assertEmpty($monorepo->getDepsDev());
        $this->assertEmpty($monorepo->getDeps());

        $autoload = $monorepo->getAutoload();
        $this->assertEmpty($autoload->getClassmap());
        $this->assertEmpty($autoload->getFiles());
        $this->assertEmpty($autoload->getPsr4());
        $this->assertCount(2, $autoload->getPsr0());
        $this->assertArrayHasKey("", $autoload->getPsr0());
        $this->assertEquals("src/", $autoload->getPsr0()[""]);
        $this->assertArrayHasKey("SymfonyStandard", $autoload->getPsr0());
        $this->assertEquals('app/', $autoload->getPsr0()['SymfonyStandard']);

        $autoloadDev = $monorepo->getAutoloadDev();
        $this->assertEmpty($autoloadDev->getFiles());
        $this->assertEmpty($autoloadDev->getPsr0());
        $this->assertCount(1, $autoloadDev->getClassmap());
        $this->assertContains('src/foo', $autoloadDev->getClassmap());
        $this->assertCount(1, $autoloadDev->getPsr4());
        $this->assertArrayHasKey('Baz', $autoloadDev->getPsr4());
        $this->assertEquals('src/baz', $autoloadDev->getPsr4()['Baz']);

        $this->assertNotEmpty($monorepo->getIncludePath());
        $this->assertContains('src/foo/bar', $monorepo->getIncludePath());

        $this->assertNotEmpty($monorepo->getBin());
        $this->assertContains('src/foo/bar/bin/binary.bin', $monorepo->getBin());
    }

    public function testLoadNonRootFromComposer()
    {
        $monorepo = $this->loader->fromComposer($this->fs->path($this->fixtureDir, 'resources','composer-test.json'), false);

        $this->assertFalse($monorepo->isRoot());
        $this->assertEquals('circlecrm/vauth', $monorepo->getName());

        $this->assertEmpty($monorepo->getRequireDev());
        $this->assertEmpty($monorepo->getRequire());

        $this->assertContains('symfony/symfony', $monorepo->getDeps());
        $this->assertContains('php', $monorepo->getDeps());
        $this->assertContains('fortawesome/font-awesome', $monorepo->getDeps());

        $this->assertContains('sensio/generator-bundle', $monorepo->getDepsDev());

        $autoload = $monorepo->getAutoload();
        $this->assertEmpty($autoload->getClassmap());
        $this->assertEmpty($autoload->getFiles());
        $this->assertEmpty($autoload->getPsr4());
        $this->assertCount(2, $autoload->getPsr0());
        $this->assertArrayHasKey("", $autoload->getPsr0());
        $this->assertEquals("src/", $autoload->getPsr0()[""]);
        $this->assertArrayHasKey("SymfonyStandard", $autoload->getPsr0());
        $this->assertEquals('app/', $autoload->getPsr0()['SymfonyStandard']);

        $autoloadDev = $monorepo->getAutoloadDev();
        $this->assertEmpty($autoloadDev->getFiles());
        $this->assertEmpty($autoloadDev->getPsr0());
        $this->assertCount(1, $autoloadDev->getClassmap());
        $this->assertContains('src/foo', $autoloadDev->getClassmap());
        $this->assertCount(1, $autoloadDev->getPsr4());
        $this->assertArrayHasKey('Baz', $autoloadDev->getPsr4());
        $this->assertEquals('src/baz', $autoloadDev->getPsr4()['Baz']);

        $this->assertNotEmpty($monorepo->getIncludePath());
        $this->assertContains('src/foo/bar', $monorepo->getIncludePath());

        $this->assertNotEmpty($monorepo->getBin());
        $this->assertContains('src/foo/bar/bin/binary.bin', $monorepo->getBin());
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