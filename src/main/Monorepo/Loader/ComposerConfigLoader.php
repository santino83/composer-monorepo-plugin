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
use Monorepo\Utils\FileUtils;

class ComposerConfigLoader
{

    /**
     * @var Factory
     */
    private $factory;

    /**
     * ComposerConfigLoader constructor.
     * @param Factory $factory
     */
    public function __construct($factory = null)
    {
        $this->factory = $factory ? $factory : new Factory();
    }

    /**
     * @param string|null $path full path to composer.json file
     * @param IOInterface|null $io
     * @return Config
     */
    public function load($path = null, $io = null)
    {
        $localConfigPath = FileUtils::file_exists($path) ? $path : null;
        $_io = $io ? $io : new NullIO();

        return $this->factory->createComposer($_io, $localConfigPath)->getConfig();
    }

}