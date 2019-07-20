<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 19/07/19
 * Time: 13.07
 */

namespace Monorepo\Loader;


use Monorepo\Composer\Util\Filesystem;
use PHPUnit\Framework\TestCase;

class DependencyTreeLoaderTest extends TestCase
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

    protected function setUp()
    {
        $this->monorepoLoader = new MonorepoLoader();
        $this->fs = new Filesystem();
        $this->loader = new DependencyTreeLoader($this->monorepoLoader, $this->fs);
        $this->fixtureDir = $this->fs->path(dirname(__DIR__),'..','_fixtures');
    }

    public function testBuildTree()
    {
        $root = $this->monorepoLoader->load($this->fs->path($this->fixtureDir, 'example-tree','monorepo.json'));

        $tree = $this->loader->load($root)->getTree();

        $this->assertEquals([$root->getName()], array_keys($tree));
        $this->assertEquals('monorepo.json', $tree[$root->getName()]['path']);
        $this->assertCount(2, $tree[$root->getName()]['children']);

        $this->assertEquals('packages/package-a/monorepo.json', $tree[$root->getName()]['children']['example/package-a']['path']);
        $this->assertCount(0, $tree[$root->getName()]['children']['example/package-a']['children']);

        $rootChildren = $tree[$root->getName()]['children'];

        $this->assertCount(1, $rootChildren['example/package-b']['children']);
        $this->assertEquals(['example/package-c'], array_keys($rootChildren['example/package-b']['children']));

        $pbChildren = $rootChildren['example/package-b']['children'];

        $this->assertCount(1, $pbChildren['example/package-c']['children']);
        $this->assertEquals(['example/package-d'], array_keys($pbChildren['example/package-c']['children']));

        $this->assertCount(0, $pbChildren['example/package-c']['children']['example/package-d']['children']);
    }
}