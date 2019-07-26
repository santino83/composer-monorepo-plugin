<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 24/07/19
 * Time: 0.45
 */

namespace Monorepo\Loader;


use Composer\Package\Link;
use Composer\Package\PackageInterface;
use Monorepo\Composer\Repository\MonorepoInstalledRepository;
use Monorepo\Composer\Package\MonorepoPackage;
use Monorepo\Composer\Util\Filesystem;
use Monorepo\Dependency\DependencyTree;
use Monorepo\Exception\MissingDependencyException;
use Monorepo\Model\Monorepo;

class MonorepoRepositoryLoader
{

    /**
     * @var DependencyTree
     */
    private $dependencyTree;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * MonorepoRepositoryLoader constructor.
     * @param DependencyTree $dependencyTree
     */
    public function __construct(DependencyTree $dependencyTree)
    {
        $this->dependencyTree = $dependencyTree;
        $this->fs = new Filesystem();
    }

    /**
     * @return MonorepoInstalledRepository
     * @throws MissingDependencyException
     */
    public function loadProjectRepository()
    {
        $this->ensureNotOrphaned();

        $root = $this->dependencyTree->getRoot();

        $rootRepository = $this->loadRootInstalledRepository($root);
        $tree = $this->dependencyTree->getTree()[$root->getName()]['children'];

        $this->doLoad($root, $rootRepository, $tree, $this->dependencyTree->isCheckingDepsDev());

        return $rootRepository;
    }

    /**
     * @param Monorepo $monorepo
     * @param MonorepoInstalledRepository|null $rootRepository
     * @return MonorepoInstalledRepository
     * @throws MissingDependencyException
     */
    public function loadMonorepoRepository($monorepo, $rootRepository = null)
    {
        if($monorepo->isRoot()){
            return $this->loadRootInstalledRepository($monorepo);
        }

        if(!$rootRepository){
            $rootRepository = $this->loadProjectRepository();
        }

        $localRepo = new MonorepoInstalledRepository();

        $deps = array_merge($monorepo->getDeps(), $this->dependencyTree->isCheckingDepsDev() ? $monorepo->getDepsDev() : []);

        foreach ($deps as $dep)
        {
            if(null !== $localRepo->findPackage($dep, "@stable") || DependencyTree::isMetaDependency($dep)){
                continue;
            }

            $package = $this->findPackage($dep, $rootRepository, $monorepo->getName());
            $this->appendPackage($package, $localRepo, $rootRepository);
        }

        return $localRepo;
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
     * @param MonorepoInstalledRepository $rootRepository
     * @param array $currentChildren
     * @param bool $includeDevs
     */
    private function doLoad($root, $rootRepository, array $currentChildren, $includeDevs = true)
    {
        foreach ($currentChildren as $monorepoName => $dependencyConfig)
        {
            $monorepo = $dependencyConfig['config'];
            /**@var $monorepo Monorepo */

            $this->processMonorepo($monorepo, $dependencyConfig, $rootRepository, $includeDevs);

            $this->doLoad($root, $rootRepository, $dependencyConfig['children'], $includeDevs);
        }
    }

    /**
     *
     * @param Monorepo $monorepo
     * @param array $dependencyConfig
     * @param MonorepoInstalledRepository $rootRepository
     * @param bool $includeDevs
     * @throws MissingDependencyException
     */
    private function processMonorepo($monorepo, $dependencyConfig, $rootRepository, $includeDevs = true)
    {
        $mainPackage = new MonorepoPackage($monorepo->getName(), '@stable','@stable');
        $mainPackage->setAutoload($monorepo->getAutoload()->toArray());
        $mainPackage->setDevAutoload($monorepo->getAutoloadDev()->toArray());
        $mainPackage->setIncludePaths($monorepo->getIncludePath());
        $mainPackage->setBinaries($monorepo->getBin());

        // set installationDir to current monorepo dir. It will use in MonorepoInstaller to
        // set the path in autoload
        $mainPackage->setRelativePathInstallation(dirname($dependencyConfig['path']));

        $deps = array_merge($monorepo->getDeps(), $includeDevs ? $monorepo->getDepsDev() : []);

        foreach($deps as $dep){

            if(null !== $rootRepository->findPackage($dep, "@stable") || DependencyTree::isMetaDependency($dep)){
                continue;
            }

            $this->ensureHasPackage($dep, $rootRepository, $monorepo->getName());
        }

        // TODO: add support for Provides and Replace on Monorepo
        if(!$rootRepository->hasPackage($mainPackage)){
            $rootRepository->addPackage($mainPackage);
        }
    }

    /**
     * @param string $packageName
     * @param MonorepoInstalledRepository $repository
     * @param $requiredBy
     * @throws MissingDependencyException
     */
    private function ensureHasPackage($packageName, $repository, $requiredBy)
    {
        $package = $repository->findPackage($packageName, '@stable');

        // TODO: move isMetaDependency in an Util Class
        if(!$package && !DependencyTree::isMetaDependency($packageName)){
            throw new MissingDependencyException([$requiredBy => $packageName]);
        }

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
     * @throws MissingDependencyException
     */
    private function ensureNotOrphaned()
    {
        if(!$this->dependencyTree->hasOrphaned()){
            return;
        }

        throw new MissingDependencyException($this->dependencyTree->getOrphaned());
    }

}