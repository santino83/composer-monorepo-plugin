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
use Composer\Package\Link;
use Composer\Package\PackageInterface;
use Monorepo\Composer\Util\Filesystem;
use Monorepo\Dependency\DependencyTree;
use Monorepo\Exception\MissingDependencyException;
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
     * @throws MissingDependencyException
     */
    public function dump($dependencyTree, $optimize = false, $cbk = null)
    {
        $this->ensureNotOrphaned($dependencyTree);

        $root = $dependencyTree->getRoot();

        $rootRepository = $this->loadRootInstalledRepository($root);
        $tree = $dependencyTree->getTree()[$root->getName()]['children'];

        $this->doDump($root, $rootRepository, $tree, $dependencyTree->isCheckingDepsDev(), $optimize, $cbk);
    }

    /**
     * @param Monorepo $root
     * @param MonorepoInstalledRepository $rootRepository
     * @param array $currentChildren
     * @param bool $dumpDevs
     * @param bool $optimize
     * @param callable|null $cbk
     * @throws MissingDependencyException
     */
    private function doDump($root, $rootRepository, array $currentChildren, $dumpDevs = true, $optimize = false, $cbk = null )
    {
        foreach ($currentChildren as $monorepoName => $dependencyConfig)
        {
            $monorepo = $dependencyConfig['config'];
            /**@var $monorepo Monorepo */

            if($cbk){
                $cbk($monorepo);
            }

            $this->dumpMonorepoAutoload($root, $monorepo, $dependencyConfig, $rootRepository, $dumpDevs, $optimize);

            $this->doDump($root, $rootRepository, $dependencyConfig['children'], $dumpDevs, $optimize, $cbk);
        }
    }

    /**
     * @param Monorepo $root
     * @param Monorepo $monorepo
     * @param array $dependencyConfig
     * @param MonorepoInstalledRepository $rootRepository
     * @param bool $dumpDevs
     * @param bool $optimize
     */
    private function dumpMonorepoAutoload($root, $monorepo, $dependencyConfig, $rootRepository, $dumpDevs, $optimize)
    {
        $rootDir = dirname($root->getPath());
        $localDir = dirname($dependencyConfig['path']);
        $binDir = $this->fs->path(dirname($monorepo->getPath()), 'vendor','bin');

        $mainPackage = new MonorepoPackage($monorepo->getName(), '@stable','@stable');
        $mainPackage->setAutoload($monorepo->getAutoload()->toArray());
        $mainPackage->setDevAutoload($monorepo->getAutoloadDev()->toArray());
        $mainPackage->setIncludePaths($monorepo->getIncludePath());
        $mainPackage->setBinaries($monorepo->getBin());

        // set installationDir to current monorepo dir. It will use in MonorepoInstaller to
        // set the path in autoload
        $mainPackage->setRelativePathInstallation(dirname($dependencyConfig['path']));

        $localRepo = new MonorepoInstalledRepository();
        $this->loadLocalInstalledRepository($monorepo, $localRepo, $rootRepository, $dumpDevs);

        // create local composer autoload
        $composerConfig = new Config(true, $rootDir);
        $composerConfig->merge(['config' => ['vendor-dir' => $this->fs->path($localDir, 'vendor')]]);

        $this->autoloadGenerator->dump(
            $composerConfig,
            $localRepo,
            $mainPackage,
            $this->installationManager,
            'composer',
            $optimize
        );

        if(!is_dir($binDir)){
            mkdir($binDir, 0755, true);
        }

        // remove old symlinks
        array_map('unlink', glob($this->fs->path($binDir,'*')));

        // add bins as symlinks
        foreach ($localRepo->getPackages() as $package) {
            /**@var $package PackageInterface */

            foreach ($package->getBinaries() as $binary) {

                $binFile = $this->fs->path($binDir , basename($binary));

                if (file_exists($binFile)) {
                     // TODO: 'Skipped installation of ' . $binFile . ' for package ' . $packageName . ': name conflicts with an existing file'
                    continue;
                }

                $this->fs->relativeSymlink($this->fs->path($rootDir , $binary), $binFile);
            }

        }

        // TODO: add support for Provides and Replace on Monorepo
        if(!$rootRepository->hasPackage($mainPackage)){
            $rootRepository->addPackage($mainPackage);
        }

    }

    /**
     * @param Monorepo $monorepo
     * @param MonorepoInstalledRepository $localRepo
     * @param MonorepoInstalledRepository $rootRepo
     * @param bool $dumpDevs
     * @throws MissingDependencyException
     */
    private function loadLocalInstalledRepository($monorepo, $localRepo, $rootRepo, $dumpDevs = true)
    {
        $deps = array_merge($monorepo->getDeps(), $dumpDevs ? $monorepo->getDepsDev() : []);

        foreach ($deps as $dep)
        {
            if(null !== $localRepo->findPackage($dep, "@stable") || DependencyTree::isMetaDependency($dep)){
                continue;
            }

            $package = $this->findPackage($dep, $rootRepo, $monorepo->getName());
            $this->appendPackage($package, $localRepo, $rootRepo);
        }

    }

    /**
     * @param PackageInterface $package
     * @param MonorepoInstalledRepository $localRepo
     * @param MonorepoInstalledRepository $rootRepo
     */
    private function appendPackage($package, $localRepo, $rootRepo)
    {
        if(!$package){
            // TODO: when package is null, do nothing. Maybe could it done better, eg: no call this method
            // when $package is null
            return;
        }

        $localRepo->addPackage($package);

        foreach(array_merge($package->getReplaces(), $package->getProvides()) as $link)
        {
            /**@var $link Link */
            $localRepo->addAlias($link->getTarget(), $package->getName(), '@stable');
        }

        foreach($package->getRequires() as $link)
        {
            /**@var $link Link */
            $required = $this->findPackage($link->getTarget(), $rootRepo, $package->getName());
            $this->appendPackage($required, $localRepo, $rootRepo);
        }
    }

    /**
     * @param string $packageName
     * @param MonorepoInstalledRepository $repository
     * @param $requiredBy
     * @return PackageInterface|null
     * @throws MissingDependencyException
     */
    private function findPackage($packageName, $repository, $requiredBy)
    {
        $package = $repository->findPackage($packageName, '@stable');

        // TODO: move isMetaDependency in an Util Class
        if(!$package && !DependencyTree::isMetaDependency($packageName)){
            throw new MissingDependencyException([$requiredBy => $packageName]);
        }

        return $package;
    }

    /**
     * @param Monorepo $root
     * @return MonorepoInstalledRepository
     */
    private function loadRootInstalledRepository($root)
    {
        $repository = new MonorepoInstalledRepository();

        $installedJsonFile = $this->fs->path(dirname($root->getPath()), $root->getVendorDir(), 'composer', 'installed.json');

        if(!file_exists($installedJsonFile)){
            return $repository;
        }

        $installed = json_decode(file_get_contents($installedJsonFile), true);

        if ($installed === NULL) {
            throw new \RuntimeException("Invalid installed.json file at " . dirname($installedJsonFile));
        }

        foreach($installed as $composerJson){

            $package = new MonorepoPackage(strtolower($composerJson['name']), '@stable', '@stable');

            // set installationDir to root's vendor-dir. It will use in MonorepoInstaller to
            // set the path in autoload
            $package->setRelativePathInstallation($this->fs->path($root->getVendorDir(),$composerJson['name']));

            if(isset($composerJson['autoload'])){
                $package->setAutoload($composerJson['autoload']);
            }

            if (isset($composerJson['autoload-dev'])) {
                $package->setAutoload(array_merge_recursive(
                    $package->getAutoload(),
                    $composerJson['autoload-dev']
                ));
            }

            if (isset($composerJson['require'])) {
                $links = [];
                foreach ($composerJson['require'] as $requiredPackageName => $requiredVersion) {
                    $links[] = new Link($package->getName(), $requiredPackageName);
                }
                $package->setRequires($links);
            }

            if (isset($composerJson['include-path'])) {
                $package->setIncludePaths($composerJson['include-path']);
            }

            if(isset($composerJson['bin'])){
                $bin = [];
                foreach ($composerJson['bin'] as $binary) {
                    $binary = $this->fs->path($root->getVendorDir(), $composerJson['name'] , $binary);
                    if (! in_array($binary, $bin)) {
                        $bin[] = $binary;
                    }
                }
                $package->setBinaries($bin);
            }

            $repository->addPackage($package);

            if (isset($composerJson['provide'])) {
                foreach ($composerJson['provide'] as $provideName => $_) {
                    $repository->addAlias($provideName, $package->getName(), $_);
                }
            }

            if (isset($composerJson['replace'])) {
                foreach ($composerJson['replace'] as $replaceName => $_) {
                    $repository->addAlias($replaceName, $package->getName(), $_);
                }
            }
        }

        return $repository;
    }

    /**
     * @param DependencyTree $dependencyTree
     * @throws MissingDependencyException
     */
    private function ensureNotOrphaned($dependencyTree)
    {
        if(!$dependencyTree->hasOrphaned()){
            return;
        }

        throw new MissingDependencyException($dependencyTree->getOrphaned());
    }

}