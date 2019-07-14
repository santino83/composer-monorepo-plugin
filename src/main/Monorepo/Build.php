<?php
/**
 * Monorepo
 *
 * LICENSE
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so I can send you a copy immediately.
 */

namespace Monorepo;

use Monorepo\Composer\MonorepoInstalledRepository;
use Monorepo\Loader\MonorepoJsonLoader;
use Monorepo\Utils\FileUtils;
use Symfony\Component\Finder\Finder;
use Composer\IO\IOInterface;
use Composer\Config;
use Composer\Factory;
use Composer\Package\Package;
use Composer\Util\Filesystem;

/**
 * Scan project for monorepo.json files, indicating components, "building" them.
 *
 * The build step is very simple and consists of generating a
 * `vendor/autoload.php` file similar to how Composer generates it.
 *
 * Prototype at Monorepo funtionality. No change detection yet.
 */
class Build
{

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var MonorepoJsonLoader
     */
    private $monorepoLoader;

    /**
     * Build constructor.
     * @param MonorepoJsonLoader $monorepoLoader
     */
    public function __construct($monorepoLoader = null)
    {
        $this->monorepoLoader = $monorepoLoader ? $monorepoLoader : new MonorepoJsonLoader();
    }

    /**
     * @param Context $context
     */
    public function build($context)
    {
        $this->io = $context->getIo();

        $this->io->write(sprintf('<info>Generating autoload files for monorepo sub-packages %s dev-dependencies.</info>', $context->isNoDevMode() ? 'without' : 'with'));
        $start = microtime(true);

        $baseConfig = $this->loadBaseConfig($context);
        $vendorDir = $baseConfig->get('vendor-dir', Config::RELATIVE_PATHS);

        $packages = $this->loadPackages($context, $baseConfig);

        $generator = $context->getGenerator();
        $installationManager = $context->getInstallationManager();

        $fsUtil = new Filesystem();

        foreach ($packages as $packageName => $config) {
            if (strpos($packageName, $vendorDir) === 0) {
                continue;
            }

            $this->io->write(sprintf(' [Subpackage] <comment>%s</comment>', $packageName));

            $mainPackage = new Package($packageName, "@stable", "@stable");
            $mainPackage->setType('monorepo');
            $mainPackage->setAutoload($config['autoload']);
            $mainPackage->setDevAutoload($config['autoload-dev']);
            $mainPackage->setIncludePaths($config['include-path']);

            $localRepo = new MonorepoInstalledRepository();
            $this->resolvePackageDependencies($localRepo, $packages, $packageName, $vendorDir, $context->isNoDevMode());

            $composerConfig = new Config(true, $context->getRootDirectory());
            $composerConfig->merge(array('config' => array('vendor-dir' => FileUtils::join_paths($config['path'], 'vendor'))));
            $generator->dump(
                $composerConfig,
                $localRepo,
                $mainPackage,
                $installationManager,
                'composer',
                $context->isOptimize()
            );

            $binDir = FileUtils::join_paths($context->getRootDirectory() , $config['path'] , 'vendor','bin');

            if (! is_dir($binDir)) {
                mkdir($binDir, 0755, true);
            }

            // remove old symlinks
            FileUtils::remove_files(glob(FileUtils::join_paths($binDir,'*')));

            foreach ($localRepo->getPackages() as $package) {
                foreach ($package->getBinaries() as $binary) {
                    $binFile = FileUtils::join_paths($binDir , basename($binary));

                    if (FileUtils::file_exists($binFile)) {
                        $this->io->write(sprintf('Skipped installation of ' . $binFile . ' for package ' . $packageName . ': name conflicts with an existing file'));
                        continue;
                    }

                    $fsUtil->relativeSymlink(FileUtils::join_paths($context->getRootDirectory() , $binary), $binFile);
                }
            }
        }

        $duration = microtime(true) - $start;

        $this->io->write(sprintf('Monorepo subpackage autoloads generated in <comment>%0.2f</comment> seconds.', $duration));
    }

    private function resolvePackageDependencies($repository, $packages, $packageName, $vendorDir, $noDevMode)
    {
        $config = $packages[$packageName];
        $dependencies = $config['deps'];

        if (!$noDevMode && isset($config['deps-dev'])) {
            $dependencies = array_merge($dependencies, $config['deps-dev']);
        }

        foreach ($dependencies as $dependencyName) {
            $isVendor = (strpos($dependencyName, $vendorDir) === 0);
            if ($dependencyName === $vendorDir . '/php' || strpos($dependencyName, $vendorDir . '/ext-') === 0 || strpos($dependencyName, $vendorDir . '/lib-') === 0) {
                continue; // Meta-dependencies that composer checks
            }

            if (!isset($packages[$dependencyName])) {
                if ($dependencyName == $vendorDir . '/composer-plugin-api') {
                    continue;
                }
                if($isVendor){
                    throw new \RuntimeException("Requiring non-existent composer-package '" . $dependencyName . "' in '" . $packageName . "'. Please ensure it is present in composer.json.");
                }else{
                    throw new \RuntimeException("Requiring non-existent repo-module '" . $dependencyName . "' in '" . $packageName . "'. Please check that the subdirectory exists, or prepend \"" . $vendorDir . "/\" to reference a composer-package.");
                }

            }

            $dependency = $packages[$dependencyName];
            $package = new Package($dependency['path'], "@stable", "@stable");
            $package->setType('monorepo');

            if (isset($dependency['autoload']) && is_array($dependency['autoload'])) {
                $package->setAutoload($dependency['autoload']);
            }

            if (isset($dependency['bin']) && is_array($dependency['bin'])) {
                $package->setBinaries($dependency['bin']);
            }

            if (isset($dependency['include-path']) && is_array($dependency['include-path'])) {
                $package->setIncludePaths($dependency['include-path']);
            }

            if (!$repository->hasPackage($package)) {
                $repository->addPackage($package);
                $this->resolvePackageDependencies($repository, $packages, $dependencyName, $vendorDir, $noDevMode);
            }
        }
    }

    /**
     * @param Context $context
     * @param string|null $baseConfig
     * @return array
     */
    public function loadPackages($context, $baseConfig = null)
    {
        $rootDirectory = $context->getRootDirectory();

        if ($baseConfig == null) {
            $baseConfig = $this->loadBaseConfig($context);
        }
        $vendorDir = $baseConfig->get('vendor-dir', Config::RELATIVE_PATHS);

        $finder = new Finder();
        $finder->in($rootDirectory)
               ->exclude($vendorDir)
               ->ignoreUnreadableDirs(true)
               ->ignoreVCS(true)
               ->name('monorepo.json');

        $packages = array();

        foreach ($finder as $file) {

            try{
                $monorepoJson = $this->monorepoLoader->fromJson($file->getContents());
            }catch (\Exception $ex){
                throw new \RuntimeException("Invalid " . $file->getRelativePath() . '/monorepo.json file:'."\n".$ex->getMessage());
            }

            $monorepoJson['path'] = $file->getRelativePath();

            if (!isset($monorepoJson['autoload'])) {
                $monorepoJson['autoload'] = array();
            }
            if (!isset($monorepoJson['autoload-dev'])) {
                $monorepoJson['autoload-dev'] = array();
            }
            if (!isset($monorepoJson['deps'])) {
                $monorepoJson['deps'] = array();
            }
            if (!isset($monorepoJson['deps-dev'])) {
                $monorepoJson['deps-dev'] = array();
            }
            if (!isset($monorepoJson['include-path'])) {
                $monorepoJson['include-path'] = array();
            }

            $packages[$file->getRelativePath()] = $monorepoJson;
        }

        $installedJsonFile = FileUtils::join_paths($rootDirectory , $vendorDir , 'composer','installed.json');
        if (file_exists($installedJsonFile)) {
            $installed = json_decode(file_get_contents($installedJsonFile), true);

            if ($installed === NULL) {
                throw new \RuntimeException("Invalid installed.json file at " . dirname($installedJsonFile));
            }

            foreach ($installed as $composerJson) {
                $name = $composerJson['name'];

                $monorepoedComposerJson = array(
                    'path' => FileUtils::join_paths($vendorDir , $name),
                    'autoload' => array(),
                    'include-path' => array(),
                    'deps' => array(),
                    'bin' => array(),
                );

                if (isset($composerJson['autoload'])) {
                    $monorepoedComposerJson['autoload'] = $composerJson['autoload'];
                }

                if (isset($composerJson['autoload-dev'])) {
                    $monorepoedComposerJson['autoload'] = array_merge_recursive(
                        $monorepoedComposerJson['autoload'],
                        $composerJson['autoload-dev']
                    );
                }

                if (isset($composerJson['require'])) {
                    foreach ($composerJson['require'] as $packageName => $_) {
                        $monorepoedComposerJson['deps'][] = $vendorDir . '/' . $packageName;
                    }
                }

                if (isset($composerJson['include-path'])) {
                    $monorepoedComposerJson['include-path'] = $composerJson['include-path'];
                }

                if (isset($composerJson['bin'])) {
                    foreach ($composerJson['bin'] as $binary) {
                        $binary = FileUtils::join_paths($vendorDir , $composerJson['name'] , $binary);
                        if (! in_array($binary, $monorepoedComposerJson['bin'])) {
                            $monorepoedComposerJson['bin'][] = $binary;
                        }
                    }
                }

                $packages[$vendorDir . '/' . strtolower($name)] = $monorepoedComposerJson;

                if (isset($composerJson['provide'])) {
                    foreach ($composerJson['provide'] as $provideName => $_) {
                        $packages[$vendorDir . '/' . $provideName] = $monorepoedComposerJson;
                    }
                }

                if (isset($composerJson['replace'])) {
                    foreach ($composerJson['replace'] as $replaceName => $_) {
                        $packages[$vendorDir . '/' . $replaceName] = $monorepoedComposerJson;
                    }
                }
            }
        }

        return $packages;
    }

    /**
     * @param Context $context
     * @return Config
     */
    private function loadBaseConfig($context) {
        $composerFactory = new Factory();
        $file = FileUtils::join_paths($context->getRootDirectory(),'composer.json');
        $localConfigPath = FileUtils::file_exists($file) ? $file : null;
        return $composerFactory->createComposer($context->getIo(), $localConfigPath)->getConfig();
    }

}
