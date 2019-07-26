<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 14/07/19
 * Time: 0.30
 */

namespace Monorepo;


use Composer\Composer;
use Composer\Config;
use Composer\EventDispatcher\EventDispatcher;
use Composer\Installer\InstallationManager;
use Composer\IO\IOInterface;
use Composer\IO\NullIO;
use Composer\Package\Version\VersionGuesser;
use Composer\Package\Version\VersionParser;
use Composer\Util\ProcessExecutor;
use Composer\Semver\VersionParser as SemverVersionParser;
use Monorepo\Composer\Autoload\AutoloadGenerator;
use Monorepo\Composer\Installer\MonorepoInstaller;
use Monorepo\Loader\ComposerLoader;
use Monorepo\Request\RequestInterface;

class ContextBuilder
{

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var MonorepoInstaller
     */
    private $monorepoInstaller;

    /**
     * @var AutoloadGenerator
     */
    private $generator;

    /**
     * @var InstallationManager
     */
    private $installationManager;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ComposerLoader
     */
    private $composerLoader;

    /**
     * @var ProcessExecutor
     */
    private $processExecutor;

    /**
     * @var Config
     */
    private $composerConfig;

    /**
     * @var SemverVersionParser
     */
    private $versionParser;

    /**
     * @var VersionGuesser
     */
    private $versionGuesser;

    private function __construct()
    {
    }

    /**
     * Creates the builder
     * @return ContextBuilder
     */
    public static function create()
    {
        return new self();
    }

    /**
     * Creates a new Context cloning the source one
     *
     * @param Context $source the context where reading from
     * @return Context the new context
     */
    public static function cloneFromContext($source)
    {
        try {

            $rClass = new \ReflectionClass($source);
            $newObj = $rClass->newInstanceWithoutConstructor();
            /**@var $newObj Context */

            foreach ($rClass->getProperties() as $property) {
                $property->setAccessible(true);
                $property->setValue($newObj, $property->getValue($source));
            }

            return $newObj;
        }catch (\Exception $ex){
            return null;
        }
    }

    /**
     * @param ComposerLoader $composerLoader
     * @return $this
     */
    public function withComposerLoader($composerLoader)
    {
        $this->composerLoader = $composerLoader;
        return $this;
    }

    /**
     * @param IOInterface $io
     * @return ContextBuilder
     */
    public function withIo($io)
    {
        $this->io = $io;
        return $this;
    }

    /**
     * @param EventDispatcher $eventDispatcher
     * @return ContextBuilder
     */
    public function withEventDispatcher($eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
        return $this;
    }

    /**
     * @param MonorepoInstaller $monorepoInstaller
     * @return ContextBuilder
     */
    public function withMonorepoInstaller($monorepoInstaller)
    {
        $this->monorepoInstaller = $monorepoInstaller;
        return $this;
    }

    /**
     * @param AutoloadGenerator $generator
     * @return ContextBuilder
     */
    public function withGenerator($generator)
    {
        $this->generator = $generator;
        return $this;
    }

    /**
     * @param InstallationManager $installationManager
     * @return ContextBuilder
     */
    public function withInstallationManager($installationManager)
    {
        $this->installationManager = $installationManager;
        return $this;
    }

    /**
     * @param RequestInterface $request
     * @return $this
     */
    public function withRequest($request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @param Config $composerConfig
     * @return $this
     */
    public function withComposerConfig($composerConfig)
    {
        $this->composerConfig = $composerConfig;
        return $this;
    }

    /**
     * @param ProcessExecutor $processExecutor
     * @return $this
     */
    public function withProcessExecutor($processExecutor)
    {
        $this->processExecutor = $processExecutor;
        return $this;
    }

    /**
     * @param SemverVersionParser $versionParser
     * @return ContextBuilder
     */
    public function withVersionParser($versionParser)
    {
        $this->versionParser = $versionParser;
        return $this;
    }

    /**
     * @param VersionGuesser $versionGuesser
     * @return ContextBuilder
     */
    public function withVersionGuesser($versionGuesser)
    {
        $this->versionGuesser = $versionGuesser;
        return $this;
    }

    /**
     * @param string $rootDirectory
     * @param bool $optimize
     * @param bool $noDevMode
     * @return Context
     */
    public function build($rootDirectory, $optimize = false, $noDevMode = false)
    {

        $context = new Context($rootDirectory, $optimize, $noDevMode);
        $context->setGenerator($this->getGenerator())
            ->setIo($this->getIo())
            ->setInstallationManager($this->getInstallationManager())
            ->setRequest($this->request)
            ->setComposerConfig($this->getComposerConfig())
            ->setProcessExecutor($this->getProcessExecutor())
            ->setVersionParser($this->getVersionParser())
            ->setVersionGuesser($this->getVersionGuesser());

        $context->getGenerator()->setDevMode(!$context->isNoDevMode());

        return $context;
    }

    /**
     * @return IOInterface
     */
    private function getIo()
    {
        if(!$this->io){
            $this->io = new NullIO();
        }

        return $this->io;
    }

    /**
     * @return EventDispatcher
     */
    private function getEventDispatcher()
    {
        if(!$this->eventDispatcher){
            $this->eventDispatcher = new EventDispatcher(new Composer(), $this->getIo());
        }

        return $this->eventDispatcher;
    }

    /**
     * @return MonorepoInstaller
     */
    private function getInstaller()
    {
        if(!$this->monorepoInstaller){
            $this->monorepoInstaller = new MonorepoInstaller();
        }

        return $this->monorepoInstaller;
    }

    /**
     * @return AutoloadGenerator
     */
    private function getGenerator()
    {
        if(!$this->generator){
            $this->generator = new AutoloadGenerator($this->getEventDispatcher(), $this->getIo());
        }

        return $this->generator;
    }

    /**
     * @return InstallationManager
     */
    private function getInstallationManager()
    {
        if(!$this->installationManager){
            $this->installationManager = new InstallationManager();
            $this->installationManager->addInstaller($this->getInstaller());
        }

        return $this->installationManager;
    }

    /**
     * @return ComposerLoader
     */
    private function getComposerLoader()
    {
        if(!$this->composerLoader)
        {
            $this->composerLoader = new ComposerLoader(null, $this->getIo());
        }

        return $this->composerLoader;
    }

    /**
     * @return Config
     */
    private function getComposerConfig()
    {
        if(!$this->composerConfig){
            $this->composerConfig = $this->getComposerLoader()->loadConfig();
        }

        return $this->composerConfig;
    }

    /**
     * @return ProcessExecutor
     */
    private function getProcessExecutor()
    {
        if(!$this->processExecutor){
            $this->processExecutor = new ProcessExecutor($this->getIo());
        }

        return $this->processExecutor;
    }

    /**
     * @return SemverVersionParser
     */
    private function getVersionParser()
    {
        if(!$this->versionParser){
            $this->versionParser = new VersionParser();
        }

        return $this->versionParser;
    }

    /**
     * @return VersionGuesser
     */
    private function getVersionGuesser()
    {
        if(!$this->versionGuesser){
            $this->versionGuesser = new VersionGuesser($this->getComposerConfig(), $this->getProcessExecutor(), $this->getVersionParser());
        }

        return $this->versionGuesser;
    }

}