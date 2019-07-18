<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 18/07/19
 * Time: 23.38
 */

namespace Monorepo\Dependency;


use Monorepo\Composer\Util\Filesystem;
use Monorepo\Loader\MonorepoLoader;
use PHPUnit\Framework\TestCase;

class DependencyTreeTest extends TestCase
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

    public function testBuildTree()
    {
        $root = $this->loader->load($this->fs->path($this->fixtureDir, 'example-tree','monorepo.json'));
        $pa = $this->loader->load($this->fs->path($this->fixtureDir, 'example-tree','packages','package-a','monorepo.json'));
        $pb = $this->loader->load($this->fs->path($this->fixtureDir, 'example-tree','packages','package-b','monorepo.json'));
        $pc = $this->loader->load($this->fs->path($this->fixtureDir, 'example-tree','packages','package-c','monorepo.json'));
        $pd = $this->loader->load($this->fs->path($this->fixtureDir, 'example-tree','packages','package-d','monorepo.json'));

        $dt = new DependencyTree($root);
        $dt->add($pb)
            ->add($pc)
            ->add($pa)
            ->add($pd);

        $tree = $dt->build();

        $this->assertEquals([$root->getName()], array_keys($tree));
        $this->assertEquals('monorepo.json', $tree[$root->getName()]['path']);
        $this->assertCount(2, $tree[$root->getName()]['children']);

        $this->assertEquals('packages/package-a/monorepo.json', $tree[$root->getName()]['children'][$pa->getName()]['path']);
        $this->assertCount(0, $tree[$root->getName()]['children'][$pa->getName()]['children']);

        $rootChildren = $tree[$root->getName()]['children'];

        $this->assertCount(1, $rootChildren[$pb->getName()]['children']);
        $this->assertEquals([$pc->getName()], array_keys($rootChildren[$pb->getName()]['children']));

        $pbChildren = $rootChildren[$pb->getName()]['children'];

        $this->assertCount(1, $pbChildren[$pc->getName()]['children']);
        $this->assertEquals([$pd->getName()], array_keys($pbChildren[$pc->getName()]['children']));

        $this->assertCount(0, $pbChildren[$pc->getName()]['children'][$pd->getName()]['children']);
    }

}