<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 19/07/19
 * Time: 12.56
 */

namespace Monorepo\Loader;


use Monorepo\Composer\Util\Filesystem;
use Monorepo\Dependency\DependencyTree;
use Monorepo\Model\Monorepo;
use Symfony\Component\Finder\Finder;

class DependencyTreeLoader
{

    /**
     * @var MonorepoLoader
     */
    private $monorepoLoader;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * DependencyTreeLoader constructor.
     * @param MonorepoLoader|null $monorepoLoader
     * @param Filesystem|null $fs
     */
    public function __construct($monorepoLoader = null, $fs = null)
    {
        $this->monorepoLoader = $monorepoLoader ? $monorepoLoader : new MonorepoLoader();
        $this->fs = $fs ? $fs : new Filesystem();
    }

    /**
     * @param Monorepo $root
     * @param bool $checkDepsDev
     * @return DependencyTree
     */
    public function load($root, $checkDepsDev = true)
    {
        $searchBaseDir = dirname($root->getPath());

        $tree = new DependencyTree($root, $checkDepsDev);

        $searchDirs =[];
        foreach($root->getPackageDirs() as $relativeSearchDir){
            $searchDirs[] = $this->fs->path($searchBaseDir, $relativeSearchDir);
        }

        $searchDirs = array_filter($searchDirs, 'file_exists');

        if(!$searchDirs){
            return $tree;
        }

        $finder = new Finder();
        $finder->in($searchDirs)
            ->exclude($root->getVendorDir())
            ->ignoreUnreadableDirs(true)
            ->ignoreVCS(true)
            ->name('monorepo.json');

        foreach($finder as $file){
            try {
                $monorepo = $this->monorepoLoader->load($file->__toString());
                $tree->add($monorepo);
            }catch (\Exception $ex){
                throw new \RuntimeException(sprintf('Unable to load monorepo from %s : %s', $file->__toString(), $ex->getMessage()), $ex->getCode(), $ex);
            }
        }

        return $tree;
    }

}