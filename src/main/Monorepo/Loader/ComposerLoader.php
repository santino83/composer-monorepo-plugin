<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 14/07/19
 * Time: 2.13
 */

namespace Monorepo\Loader;


use Composer\Config;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\IO\NullIO;

class ComposerLoader
{

    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * ComposerLoader constructor.
     * @param Factory|null $factory
     * @param IOInterface|null $io
     */
    public function __construct($factory = null, IOInterface $io = null)
    {
        $this->factory = $factory ? $factory : new Factory();
        $this->io = $io ? $io : new NullIO();
    }

    /**
     * @param string|null $path full path to composer.json file
     * @param IOInterface|null $io
     * @return Config
     */
    public function loadConfig($path = null, $io = null)
    {
        return $this->loadComposer($path, $io)->getConfig();
    }

    /**
     * @param string|null $path to composer.json file
     * @param IOInterface|null $io
     * @return \Composer\Composer
     */
    public function loadComposer($path = null, $io = null)
    {
        $localConfigPath = file_exists($path) ? $path : null;
        $_io = $io ? $io : $this->io;

        return $this->factory->createComposer($_io, $localConfigPath);
    }

}