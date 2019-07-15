<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 15/07/19
 * Time: 0.30
 */

namespace Monorepo;


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
        $composer = $this->getComposer($composerPath, $io);

        // load monorepo from composer info
        $monorepo = $this->monorepoLoader->fromComposer($composer, true);

        // create monorepo.json
        $this->writeMonorepo($monorepo, $monorepoPath);

        // launch build (?)

        // final check
        if(!$this->isMonorepoInitialized($monorepoPath)){
            throw new \RuntimeException('Monorepo project not initialized. Try again');
        }

        // end
        $context->getIo()->write('<info>Done!</info>');
    }

    /**
     * @param Monorepo $monorepo
     * @param string $path
     */
    protected function writeMonorepo($monorepo, $path)
    {
        $monorepoJson = json_encode($monorepo->toArray(), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        try{
            file_put_contents($path, $monorepoJson);
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