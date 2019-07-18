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
use Monorepo\Composer\Util\Filesystem;
use Monorepo\Loader\ComposerLoader;
use Monorepo\Loader\MonorepoLoader;
use Monorepo\Model\Monorepo;

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
        if($this->isMonorepoInitialized($monorepoPath)){
            throw new \RuntimeException('Monorepo project already initialized');
        }

        // load composer.json (or error)
        if(!$this->isComposerInitialized($composerPath)){
            throw new \RuntimeException('It seems not to be a composer project. Run "composer init" first');
        }

        // load composer
        $composer = $this->getComposer($composerPath, $context->getIo());

        // load monorepo from composer info
        $monorepo = $this->monorepoLoader->fromComposer($composer, true);
        $monorepo->setPath($monorepoPath);

        $this->doUpdateMonorepo($monorepo);

        // launch build (?)

        // final check
        if(!$this->isMonorepoInitialized($monorepo->getPath())){
            throw new \RuntimeException('Monorepo project not initialized. Try again');
        }

        // end
        $io->write('<info>Done!</info>');
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

        $io->write('<info>Updating monorepo.json</info>');

        $composerPath = $this->getRootComposerPath($rootDir);

        // load composer
        $composer = $this->getComposer($composerPath, $context->getIo());
        $monorepo = $this->loadMonorepo($rootDir);

        $this->doUpdateMonorepo($monorepo, $composer);
    }

    /**
     * Updates all monorepo subpackages
     *
     * @param Context $context
     */
    public function build($context)
    {
        $io = $context->getIo();

        $rootDir = $context->getRootDirectory();
        $rootMonorepoPath = $this->getRootMonorepoPath($rootDir);
        $composerPath = $this->getRootComposerPath($rootDir);

        if(!$this->isMonorepoInitialized($rootMonorepoPath))
        {
            // do nothing if project is not initialized
            return;
        }

        $rootMonorepo = $this->loadMonorepo($rootMonorepoPath);


    }

    /**
     * @param Monorepo $monorepo
     * @return array|string
     */
    protected function getPackageDirs($monorepo)
    {
        if(!$monorepo->getPackageDirs()){
            return ['packages','lib','src'];
        }

        return $monorepo->getPackageDirs();
    }

    /**
     * @param string $basePath
     * @return Monorepo
     */
    protected function loadMonorepo($basePath)
    {
        return $this->monorepoLoader->load($this->fs->path($basePath, 'monorepo.json'));
    }

    /**
     * Updates main monorepo.json file
     * @param Monorepo $monorepo
     * @param string|Composer|null $composer
     */
    protected function doUpdateMonorepo($monorepo, $composer = null)
    {

        if($composer){

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

        try{
            file_put_contents($monorepo->getPath(), $monorepoJson);
        }catch (\Exception $ex){
            throw new \RuntimeException(sprintf("Unable to write monorepo.json at %s:\n%s", $path, $ex->getMessage()), $ex->getCode(), $ex);
        }
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

}