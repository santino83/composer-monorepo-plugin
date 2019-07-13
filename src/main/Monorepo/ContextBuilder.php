<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 14/07/19
 * Time: 0.30
 */

namespace Monorepo;


use Composer\Composer;
use Composer\Installer\InstallationManager;
use Composer\IO\IOInterface;
use Composer\IO\NullIO;
use Monorepo\Composer\AutoloadGenerator;
use Monorepo\Composer\EventDispatcher;
use Monorepo\Composer\MonorepoInstaller;

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
            ->setInstallationManager($this->getInstallationManager());

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
    private function getMonorepoInstaller()
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
            $this->installationManager->addInstaller($this->getMonorepoInstaller());
        }

        return $this->installationManager;
    }

}