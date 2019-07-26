<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 19/07/19
 * Time: 15.18
 */

namespace Monorepo\Composer;


use Composer\Config;
use Composer\Installer\InstallationManager;
use Composer\Package\PackageInterface;
use Monorepo\Composer\Autoload\AutoloadGenerator;
use Monorepo\Composer\Repository\MonorepoInstalledRepository;
use Monorepo\Composer\Util\Filesystem;
use Monorepo\Dependency\DependencyTree;
use Monorepo\Loader\MonorepoRepositoryLoader;
use Monorepo\Model\Monorepo;

class VendorDependencyDumper
{

    /**
     * @var AutoloadGenerator
     */
    private $autoloadGenerator;

    /**
     * @var InstallationManager
     */
    private $installationManager;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * VendorDependencyDumper constructor.
     * @param AutoloadGenerator $autoloadGenerator
     * @param InstallationManager $installationManager
     * @param Filesystem|null $fs
     */
    public function __construct($autoloadGenerator, $installationManager, $fs = null)
    {
        $this->autoloadGenerator = $autoloadGenerator;
        $this->installationManager = $installationManager;
        $this->fs = $fs ? $fs : new Filesystem();
    }

    /**
     * Dumps the composer autoload configuration.
     * callback is invoked passing the current processed monorepo (no root one)
     *
     * @param DependencyTree $dependencyTree
     * @param bool $optimize optimize autoload flag
     * @param callable|null $cbk optional callback to inkove to notify current processing package
     */
    public function dump($dependencyTree, $optimize = false, $cbk = null)
    {
        $root = $dependencyTree->getRoot();

        $repositoryLoader = new MonorepoRepositoryLoader($dependencyTree);
        $rootRepository = $repositoryLoader->loadProjectRepository();

        $tree = $dependencyTree->getTree()[$root->getName()]['children'];

        $this->doDump($root, $rootRepository, $repositoryLoader, $tree, $dependencyTree->isCheckingDepsDev(), $optimize, $cbk);
    }

    /**
     * @param Monorepo $root
     * @param MonorepoInstalledRepository $rootRepository
     * @param MonorepoRepositoryLoader $repositoryLoader
     * @param array $currentChildren
     * @param bool $dumpDevs
     * @param bool $optimize
     * @param callable|null $cbk
     */
    private function doDump($root, $rootRepository, $repositoryLoader, array $currentChildren, $dumpDevs = true, $optimize = false, $cbk = null)
    {
        foreach ($currentChildren as $monorepoName => $dependencyConfig) {
            $monorepo = $dependencyConfig['config'];
            /**@var $monorepo Monorepo */

            if ($cbk) {
                $cbk($monorepo);
            }

            $this->dumpMonorepoAutoload($root, $monorepo, $dependencyConfig, $rootRepository, $repositoryLoader, $dumpDevs, $optimize);

            $this->doDump($root, $rootRepository, $repositoryLoader, $dependencyConfig['children'], $dumpDevs, $optimize, $cbk);
        }
    }

    /**
     * @param Monorepo $root
     * @param Monorepo $monorepo
     * @param array $dependencyConfig
     * @param MonorepoInstalledRepository $rootRepository
     * @param MonorepoRepositoryLoader $repositoryLoader
     * @param bool $dumpDevs
     * @param bool $optimize
     */
    private function dumpMonorepoAutoload($root, $monorepo, $dependencyConfig, $rootRepository, $repositoryLoader, $dumpDevs, $optimize)
    {
        $rootDir = dirname($root->getPath());
        $localDir = dirname($dependencyConfig['path']);
        $vendorDir = $monorepo->getVendorDir();
        $binDir = $this->fs->path(dirname($monorepo->getPath()), $vendorDir, 'bin');

        $mainPackage = $rootRepository->findPackage($monorepo->getName(), "@stable");

        $localRepo = $repositoryLoader->loadMonorepoRepository($monorepo, $rootRepository);

        // create local composer autoload
        $composerConfig = new Config(true, $rootDir);
        $composerConfig->merge(['config' => ['vendor-dir' => $this->fs->path($localDir, $vendorDir)]]);

        $this->autoloadGenerator->dump(
            $composerConfig,
            $localRepo,
            $mainPackage,
            $this->installationManager,
            'composer',
            $optimize
        );

        if (!is_dir($binDir)) {
            mkdir($binDir, 0755, true);
        }

        // remove old symlinks
        array_map('unlink', glob($this->fs->path($binDir, '*')));

        // add bins as symlinks
        foreach ($localRepo->getPackages() as $package) {
            /**@var $package PackageInterface */

            foreach ($package->getBinaries() as $binary) {

                $binFile = $this->fs->path($binDir, basename($binary));

                if (file_exists($binFile)) {
                    // TODO: 'Skipped installation of ' . $binFile . ' for package ' . $packageName . ': name conflicts with an existing file'
                    continue;
                }

                $this->fs->relativeSymlink($this->fs->path($rootDir, $binary), $binFile);
            }

        }

    }

}