<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 13/07/19
 * Time: 21.51
 */

namespace Monorepo;


use Composer\Installer\InstallationManager;
use Composer\IO\IOInterface;
use Monorepo\Composer\AutoloadGenerator;
use Monorepo\Composer\Util\Filesystem;

class Context
{
    /**
     * The monorepo project root directory
     * @var string
     */
    private $rootDirectory;

    /**
     * Optimize autoloader flag (default false)
     * @var bool
     */
    private $optimize = false;

    /**
     * Exclude dev packages (default false)
     * @var bool
     */
    private $noDevMode = false;

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var AutoloadGenerator
     */
    private $generator;

    /**
     * @var InstallationManager
     */
    private $installationManager;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Context constructor.
     * @param string $rootDirectory
     * @param bool $optimize
     * @param bool $noDevMode
     */
    public function __construct($rootDirectory, $optimize = false, $noDevMode = false)
    {
        $this->rootDirectory = $rootDirectory;
        $this->optimize = $optimize;
        $this->noDevMode = $noDevMode;
    }

    /**
     * @return string
     */
    public function getRootDirectory()
    {
        return $this->rootDirectory;
    }

    /**
     * @return bool
     */
    public function isOptimize()
    {
        return $this->optimize;
    }

    /**
     * @return bool
     */
    public function isNoDevMode()
    {
        return $this->noDevMode;
    }

    /**
     * @return IOInterface
     */
    public function getIo()
    {
        return $this->io;
    }

    /**
     * @param IOInterface $io
     * @return Context
     */
    public function setIo($io)
    {
        $this->io = $io;
        return $this;
    }

    /**
     * @return AutoloadGenerator
     */
    public function getGenerator()
    {
        return $this->generator;
    }

    /**
     * @param AutoloadGenerator $generator
     * @return Context
     */
    public function setGenerator($generator)
    {
        $this->generator = $generator;
        return $this;
    }

    /**
     * @return InstallationManager
     */
    public function getInstallationManager()
    {
        return $this->installationManager;
    }

    /**
     * @param InstallationManager $installationManager
     * @return Context
     */
    public function setInstallationManager($installationManager)
    {
        $this->installationManager = $installationManager;
        return $this;
    }

    /**
     * @return Filesystem
     */
    public function getFilesystem()
    {
        return $this->filesystem;
    }

    /**
     * @param Filesystem $filesystem
     * @return Context
     */
    public function setFilesystem($filesystem)
    {
        $this->filesystem = $filesystem;
        return $this;
    }

}