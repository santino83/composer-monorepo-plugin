<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 15/07/19
 * Time: 0.30
 */

namespace Monorepo;


use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Monorepo\Composer\MonorepoComposerBuilder;
use Monorepo\Composer\Util\Filesystem;
use Monorepo\Composer\VendorDependencyDumper;
use Monorepo\Exception\MissingDependencyException;
use Monorepo\Loader\ComposerLoader;
use Monorepo\Loader\DependencyTreeLoader;
use Monorepo\Loader\MonorepoLoader;
use Monorepo\Model\Autoload;
use Monorepo\Model\Monorepo;
use Monorepo\Request\AddMonorepoRequest;
use Monorepo\Request\BuildMonorepoRequest;
use Monorepo\Request\InitMonorepoRequest;
use Monorepo\Util\StringUtils;

class Console
{

    /**
     * @var MonorepoLoader
     */
    private $monorepoLoader;

    /**
     * @var ComposerLoader
     */
    private $composerLoader;

    /**
     * @var DependencyTreeLoader
     */
    private $dependencyTreeLoader;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * Console constructor.
     * @param MonorepoLoader|null $monorepoLoader
     * @param ComposerLoader|null $composerLoader
     * @param Filesystem|null $fs
     */
    public function __construct($monorepoLoader = null, $composerLoader = null, $fs = null)
    {
        $this->monorepoLoader = $monorepoLoader ? $monorepoLoader : new MonorepoLoader();
        $this->composerLoader = $composerLoader ? $composerLoader : new ComposerLoader();
        $this->fs = $fs ? $fs : new Filesystem();
        $this->dependencyTreeLoader = new DependencyTreeLoader($this->monorepoLoader, $this->fs);
    }

    /**
     * Initializes a monorepo project
     *
     * @param Context $context
     */
    public function init($context)
    {
        $io = $context->getIo();

        $rootDir = $context->getRootDirectory();
        $monorepoPath = $this->getRootMonorepoPath($rootDir);
        $composerPath = $this->getRootComposerPath($rootDir);

        $io->write('<info>Initializing monorepo project...</info>');

        // check already init, and throws error in case
        if ($this->isMonorepoInitialized($monorepoPath)) {
            throw new \RuntimeException('Monorepo project already initialized');
        }

        // load composer.json (or error)
        if (!$this->isComposerInitialized($composerPath)) {
            throw new \RuntimeException('It seems not to be a composer project. Run "composer init" first');
        }

        // load composer
        $composer = $this->getComposer($composerPath, $context->getIo());

        // load monorepo from composer info
        $monorepo = $this->monorepoLoader->fromComposer($composer, true);
        $monorepo->setPath($monorepoPath)
            ->setNamespace(StringUtils::toPascal(basename(dirname($monorepoPath))));

        if ($context->getRequest() && $context->getRequest() instanceof InitMonorepoRequest) {
            $monorepo->setNamespace($context->getRequest()->getNamespace());
        }

        $this->doUpdateMonorepo($monorepo);

        // final check
        if (!$this->isMonorepoInitialized($monorepo->getPath())) {
            throw new \RuntimeException('Monorepo project not initialized. Try again');
        }

        // end
        $io->write('<info>Done!</info>');
    }

    /**
     * Updates all monorepo subpackages
     *
     * @param Context $context
     */
    public function dump($context)
    {
        $io = $context->getIo();

        $rootDir = $context->getRootDirectory();
        $rootMonorepoPath = $this->getRootMonorepoPath($rootDir);

        if (!$this->isMonorepoInitialized($rootMonorepoPath)) {
            // do nothing if project is not initialized
            return;
        }

        $io->write(sprintf('<info>Generating autoload files for monorepo sub-packages %s dev-dependencies.</info>', $context->isNoDevMode() ? 'without' : 'with'));

        $start = microtime(true);

        $rootMonorepo = $this->loadMonorepo($rootMonorepoPath);
        $dependencyTree = $this->dependencyTreeLoader->load($rootMonorepo, !$context->isNoDevMode());

        $vendorDump = new VendorDependencyDumper($context->getGenerator(), $context->getInstallationManager(), $this->fs);

        try {
            $vendorDump->dump($dependencyTree, $context->isOptimize(), function ($processedMonorepo) use ($io) {
                /**@var $processedMonorepo Monorepo */
                $io->write(sprintf(' [Subpackage] <comment>%s</comment>', $processedMonorepo->getName()));
            });
        } catch (MissingDependencyException $ex) {

            $io->write(sprintf('<error>%s</error>', $ex->getMessage()));

            foreach ($ex->getOrphaned() as $packageName => $deps) {
                $io->write(sprintf('<info>%s</info> missing dependencies: <error>%s</error>', $packageName, implode(',', (array)$deps)));
            }
        }

        $duration = microtime(true) - $start;

        $io->write(sprintf('Monorepo subpackage autoloads generated in <comment>%0.2f</comment> seconds.', $duration));
    }

    /**
     * @param Context $context
     */
    public function build($context)
    {
        $io = $context->getIo();

        $rootDir = $context->getRootDirectory();
        $rootMonorepoPath = $this->getRootMonorepoPath($rootDir);

        if (!$this->isMonorepoInitialized($rootMonorepoPath)) {
            // do nothing if project is not initialized
            return;
        }

        $request = $context->getRequest();
        /**@var $request BuildMonorepoRequest */

        if (!$request || !($request instanceof BuildMonorepoRequest)) {
            throw new \RuntimeException('Invalid input');
        }

        $rootMonorepo = $this->loadMonorepo($rootMonorepoPath);
        $buildDir = $this->fs->path($rootDir, $rootMonorepo->getBuildDir());
        $dependencyTree = $this->dependencyTreeLoader->load($rootMonorepo);

        $packages = $request->getPackages() ? $request->getPackages() : [];

        if(!$packages && !$request->isBuildAll()){
            throw new \RuntimeException('Invalid packages list to build');
        }

        if(!$packages){
            $packages = array_diff($dependencyTree->getMonorepos(), [$rootMonorepo->getName()]);
        }else{
            foreach($packages as $package){
                if(!$dependencyTree->has($package)){
                    throw new \RuntimeException(sprintf('Package %s doesn\'t exist in project', $package));
                }
            }
        }

        $this->fs->ensureDirectoryExists($buildDir);

        $builder = new MonorepoComposerBuilder($this->fs);

        foreach($packages as $package){
            $io->write('<info>Building package </info>'.$package.' <info>...</info>');
            $builder->build($dependencyTree->get($package), $rootMonorepo, $buildDir, $request->getVersion());
        }

        $io->write('<info>Done!</info>');
    }

    /**
     * Adds a new sub-module to root module
     *
     * @param Context $context
     */
    public function add($context)
    {
        $io = $context->getIo();

        $rootDir = $context->getRootDirectory();
        $request = $context->getRequest();
        /**@var $request AddMonorepoRequest */

        if (!$request || !($request instanceof AddMonorepoRequest)) {
            throw new \RuntimeException('Invalid input');
        }

        $io->write(sprintf('<info>Generating package %s</info>', $request->getName()));

        $monorepoBasePath = $this->fs->path($rootDir, $request->getPackageDir());
        $monorepoAutoloadSrcPath = $this->fs->path($monorepoBasePath, 'src');
        $monorepoPath = $this->fs->path($monorepoBasePath, 'monorepo.json');

        if (file_exists($monorepoPath)) {
            throw new \RuntimeException(sprintf('Directory %s already exists and contains a monorepo', $monorepoBasePath));
        }

        try {
            mkdir($monorepoBasePath, 0755, true);
        } catch (\Exception $ex) {
            throw new \RuntimeException(sprintf('Unable to create %s : %s', $monorepoBasePath, $ex->getMessage()), $ex->getCode(), $ex);
        }

        if (!file_exists($monorepoAutoloadSrcPath)) {
            try {
                mkdir($monorepoAutoloadSrcPath, 0755, true);
            } catch (\Exception $ex) {
                throw new \RuntimeException(sprintf('Unable to create %s : %s', $monorepoAutoloadSrcPath, $ex->getMessage()), $ex->getCode(), $ex);
            }
        }

        $monorepo = new Monorepo(false, $monorepoPath);
        $monorepo->setName($request->getName())
            ->setDeps($request->getDeps())
            ->setDepsDev($request->getDepsDev())
            ->setAutoload(Autoload::fromArray(['psr-4' => [$request->getNamespace() . '\\' => 'src' . DIRECTORY_SEPARATOR]]));

        $this->writeMonorepo($monorepo);

        if (!file_exists($monorepo->getPath())) {
            throw new \RuntimeException('Unable to create monorepo.json. Try again');
        }

        $io->write(sprintf('<info>Package %s generated</info>', $monorepo->getName()));

        $this->dump($context);
    }

    /**
     * Updates monorepo project
     *
     * @param Context $context
     */
    public function update($context)
    {
        $io = $context->getIo();

        $rootDir = $context->getRootDirectory();
        $monorepoPath = $this->getRootMonorepoPath($rootDir);

        if (!$this->isMonorepoInitialized($monorepoPath)) {
            // do nothing if project is not initialized
            return;
        }

        $io->write('<info>Updating main monorepo.json</info>');

        $composerPath = $this->getRootComposerPath($rootDir);

        // load composer
        $composer = $this->getComposer($composerPath, $context->getIo());
        $monorepo = $this->loadMonorepo($monorepoPath);

        $this->doUpdateMonorepo($monorepo, $composer);

        // update all monorepo subpackages
        $this->dump($context);
    }

    /**
     * @param Context $context
     * @return string|null
     */
    public function projectVersion($context)
    {
        $rootDir = $context->getRootDirectory();
        $composerFile = $this->getRootComposerPath($rootDir);

        if (!$this->isComposerInitialized($composerFile)) {
            throw new \RuntimeException('It seems not to be a composer project. Run "composer init" first');
        }

        $composerPackageFile = new JsonFile($composerFile, null, $context->getIo());
        $composerPackage = $composerPackageFile->read();

        $version = $context->getVersionGuesser()->guessVersion($composerPackage, $rootDir);

        return $version ? $version['pretty_version'] : null;
    }

    /**
     * Returns the current root monorepo
     *
     * @param Context $context
     * @return Monorepo
     */
    public function rootMonorepo($context)
    {
        $rootDir = $context->getRootDirectory();
        $rootMonorepoPath = $this->getRootMonorepoPath($rootDir);

        if (!$this->isMonorepoInitialized($rootMonorepoPath)) {
            throw new \RuntimeException('Monorepo project not initialized. Run monorepo:init before');
        }

        return $this->loadMonorepo($rootMonorepoPath);
    }

    /**
     * @param string $rootDir
     * @return string
     */
    protected function getRootMonorepoPath($rootDir)
    {
        return $this->fs->path($rootDir, 'monorepo.json');
    }

    /**
     * @param string $rootDir
     * @return string
     */
    protected function getRootComposerPath($rootDir)
    {
        return $this->fs->path($rootDir, 'composer.json');
    }

    /**
     * @param string $path
     * @return bool
     */
    protected function isMonorepoInitialized($path)
    {
        return file_exists($path);
    }

    /**
     * @param string $path
     * @return bool
     */
    protected function isComposerInitialized($path)
    {
        return file_exists($path);
    }

    /**
     * @param string|null $path
     * @param IOInterface|null $io
     * @return \Composer\Composer
     */
    protected function getComposer($path = null, $io = null)
    {
        return $this->composerLoader->loadComposer($path, $io);
    }

    /**
     * Updates main monorepo.json file
     * @param Monorepo $monorepo
     * @param string|Composer|null $composer
     */
    protected function doUpdateMonorepo($monorepo, $composer = null)
    {

        if ($composer) {

            // load updated monorepo from composer
            $composerMonorepo = $this->monorepoLoader->fromComposer($composer, true);

            // merge monorepo.json with composer.json data
            $monorepo->merge($composerMonorepo);
        }

        // create/update monorepo.json
        $this->writeMonorepo($monorepo);
    }

    /**
     * @param Monorepo $monorepo
     */
    protected function writeMonorepo($monorepo)
    {
        $monorepoJson = json_encode($monorepo->toArray(), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        try {
            file_put_contents($monorepo->getPath(), $monorepoJson);
        } catch (\Exception $ex) {
            throw new \RuntimeException(sprintf("Unable to write monorepo.json at %s:\n%s", $monorepo->getPath(), $ex->getMessage()), $ex->getCode(), $ex);
        }
    }

    /**
     * @param string $path
     * @return Monorepo
     */
    protected function loadMonorepo($path)
    {
        return $this->monorepoLoader->load($path);
    }

}