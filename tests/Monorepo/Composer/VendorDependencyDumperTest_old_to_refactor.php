<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 20/07/19
 * Time: 1.26
 */

namespace Monorepo\Composer;


use Composer\Installer\InstallationManager;
use Composer\Package\Link;
use Composer\Package\Package;
use Monorepo\Composer\Autoload\AutoloadGenerator;
use Monorepo\Composer\Repository\MonorepoInstalledRepository;
use Monorepo\Composer\Util\Filesystem;
use Monorepo\Loader\DependencyTreeLoader;
use Monorepo\Loader\MonorepoLoader;
use PHPUnit\Framework\TestCase;

//TODO: move tests in correct new classes

class VendorDependencyDumperTestOldtorefactor extends TestCase
{
    /**
     * @var MonorepoLoader
     */
    private $monorepoLoader;

    /**
     * @var DependencyTreeLoader
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

    /**
     * @var VendorDependencyDumper
     */
    private $dumper;

    /**
     * @var AutoloadGenerator
     */
    private $generator;

    /**
     * @var InstallationManager
     */
    private $installationManager;

    protected function setUp()
    {
        $this->monorepoLoader = new MonorepoLoader();
        $this->fs = new Filesystem();
        $this->loader = new DependencyTreeLoader($this->monorepoLoader, $this->fs);
        $this->fixtureDir = $this->fs->path(dirname(__DIR__),'..','_fixtures');
        $this->generator = \Phake::mock(AutoloadGenerator::class);
        $this->installationManager = \Phake::mock(InstallationManager::class);
        $this->dumper = new VendorDependencyDumper($this->generator, $this->installationManager, $this->fs);
    }

    public function testExample_Simple_NO_ORPHANED()
    {
        $root = $this->monorepoLoader->load($this->fs->path($this->fixtureDir,'example-simple','monorepo.json'));
        $dependencyTree = $this->loader->load($root);

        $this->invokeMethod($this->dumper, 'ensureNotOrphaned', [$dependencyTree]);
        $this->assertTrue(true);
    }

    public function testExample_Simple_LOAD_ROOT_INSTALLED_REPOSITORY()
    {
        $root = $this->monorepoLoader->load($this->fs->path($this->fixtureDir,'example-simple','monorepo.json'));

        $repository = $this->invokeMethod($this->dumper, 'loadRootInstalledRepository',[$root]);
        /**@var $repository MonorepoInstalledRepository */

        $this->assertNotNull($repository);
        $this->assertCount(2, $repository->getPackages());

        $package = $repository->findPackage('foo/baz','@stable');
        $this->assertNotNull($package);
    }

    public function testExample_Simple_LOAD_LOCAL_INSTALLED_REPOSITORY_BAR()
    {
        $root = $this->monorepoLoader->load($this->fs->path($this->fixtureDir,'example-simple','monorepo.json'));

        $rootRepository = $this->invokeMethod($this->dumper, 'loadRootInstalledRepository',[$root]);
        /**@var $rootRepository MonorepoInstalledRepository */

        $monorepo = $this->monorepoLoader->load($this->fs->path($this->fixtureDir,'example-simple','bar','monorepo.json'));
        $localRepo = new MonorepoInstalledRepository();

        $this->invokeMethod($this->dumper, 'loadLocalInstalledRepository',[$monorepo, $localRepo, $rootRepository, true]);

        $packages = $localRepo->getPackages();
        $this->assertNotEmpty($packages);
        $this->assertCount(1, $packages);
        $this->assertEquals('foo/baz', $packages[array_keys($packages)[0]]->getName());
    }

    public function testExample_Simple_LOAD_LOCAL_INSTALLED_REPOSITORY_FOO()
    {
        $root = $this->monorepoLoader->load($this->fs->path($this->fixtureDir,'example-simple','monorepo.json'));

        $rootRepository = $this->invokeMethod($this->dumper, 'loadRootInstalledRepository',[$root]);
        /**@var $rootRepository MonorepoInstalledRepository */

        $bar = new Package('example-simple/bar',"@stable","@stable");
        $bar->setRequires([new Link('example-simple/bar','foo/baz')]);

        $rootRepository->addPackage($bar);
        $rootRepository->addPackage(new Package('foo/baz',"@stable","@stable"));

        $monorepo = $this->monorepoLoader->load($this->fs->path($this->fixtureDir,'example-simple','foo','monorepo.json'));
        $localRepo = new MonorepoInstalledRepository();

        $this->invokeMethod($this->dumper, 'loadLocalInstalledRepository',[$monorepo, $localRepo, $rootRepository, true]);

        $packages = $localRepo->getPackages();

        $this->assertNotEmpty($packages);
        $this->assertCount(3, $packages);
        $this->assertEquals(['example-simple/bar','foo/baz','bar/baz'], array_keys($packages));
    }

    public function testExample_Simple_LOAD_LOCAL_INSTALLED_REPOSITORY_FOO_NO_DEV()
    {
        $root = $this->monorepoLoader->load($this->fs->path($this->fixtureDir,'example-simple','monorepo.json'));

        $rootRepository = $this->invokeMethod($this->dumper, 'loadRootInstalledRepository',[$root]);
        /**@var $rootRepository MonorepoInstalledRepository */

        $bar = new Package('example-simple/bar',"@stable","@stable");
        $bar->setRequires([new Link('example-simple/bar','foo/baz')]);

        $rootRepository->addPackage($bar);
        $rootRepository->addPackage(new Package('foo/baz',"@stable","@stable"));

        $monorepo = $this->monorepoLoader->load($this->fs->path($this->fixtureDir,'example-simple','foo','monorepo.json'));
        $localRepo = new MonorepoInstalledRepository();

        $this->invokeMethod($this->dumper, 'loadLocalInstalledRepository',[$monorepo, $localRepo, $rootRepository, false]);

        $packages = $localRepo->getPackages();

        $this->assertNotEmpty($packages);
        $this->assertCount(2, $packages);
        $this->assertEquals(['example-simple/bar','foo/baz'], array_keys($packages));
    }



    /**
     * Call protected/private method of a class.
     *
     * @param mixed $object    Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     * @throws \Exception
     */
    protected function invokeMethod($object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}