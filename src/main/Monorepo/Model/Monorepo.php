<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 14/07/19
 * Time: 18.53
 */

namespace Monorepo\Model;


class Monorepo
{

    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $root = false;

    /**
     * @var Dependency
     */
    private $require;

    /**
     * @var Dependency
     */
    private $requireDev;

    /**
     * @var array|string[]
     */
    private $deps = [];

    /**
     * @var array|string[]
     */
    private $depsDev = [];

    /**
     * @var Autoload
     */
    private $autoload;

    /**
     * @var Autoload
     */
    private $autoloadDev;

    /**
     * @var array|string[]
     */
    private $includePath = [];

    /**
     * @var array|string[]
     */
    private $bin = [];

    /**
     * @var array|string[]
     */
    private $packageDirs = [];

    /**
     * Monorepo constructor.
     * @param bool $root
     */
    public function __construct($root = false)
    {
        $this->root = $root;
        $this->require = new Dependency();
        $this->requireDev = new Dependency();
        $this->autoload = new Autoload();
        $this->autoloadDev = new Autoload();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Monorepo
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return bool
     */
    public function isRoot()
    {
        return $this->root;
    }

    /**
     * @param bool $root
     * @return Monorepo
     */
    public function setRoot($root)
    {
        $this->root = $root;
        return $this;
    }

    /**
     * @return Autoload
     */
    public function getAutoload()
    {
        return $this->autoload;
    }

    /**
     * @param Autoload $autoload
     * @return Monorepo
     */
    public function setAutoload($autoload)
    {
        $this->autoload = $autoload;
        return $this;
    }

    /**
     * @return Autoload
     */
    public function getAutoloadDev()
    {
        return $this->autoloadDev;
    }

    /**
     * @param Autoload $autoloadDev
     * @return Monorepo
     */
    public function setAutoloadDev($autoloadDev)
    {
        $this->autoloadDev = $autoloadDev;
        return $this;
    }

    /**
     * @return array|string[]
     */
    public function getIncludePath()
    {
        return $this->includePath;
    }

    /**
     * @param array|string[] $includePath
     * @return Monorepo
     */
    public function setIncludePath(array $includePath)
    {
        $this->includePath = $includePath;
        return $this;
    }

    /**
     * @return array|string[]
     */
    public function getBin()
    {
        return $this->bin;
    }

    /**
     * @param array|string[] $bin
     * @return Monorepo
     */
    public function setBin(array $bin)
    {
        $this->bin = $bin;
        return $this;
    }

    /**
     * @return array|string[]
     */
    public function getPackageDirs()
    {
        return $this->packageDirs;
    }

    /**
     * @param array|string[] $packageDirs
     * @return Monorepo
     */
    public function setPackageDirs(array $packageDirs)
    {
        $this->packageDirs = $packageDirs;
        return $this;
    }

    /**
     * @return Dependency
     */
    public function getRequire()
    {
        return $this->require;
    }

    /**
     * @param Dependency $require
     * @return Monorepo
     */
    public function setRequire($require)
    {
        $this->require = $require;
        return $this;
    }

    /**
     * @return Dependency
     */
    public function getRequireDev()
    {
        return $this->requireDev;
    }

    /**
     * @param Dependency $requireDev
     * @return Monorepo
     */
    public function setRequireDev($requireDev)
    {
        $this->requireDev = $requireDev;
        return $this;
    }

    /**
     * @return array|string[]
     */
    public function getDeps()
    {
        return $this->deps;
    }

    /**
     * @param array|string[] $deps
     * @return Monorepo
     */
    public function setDeps(array $deps)
    {
        $this->deps = $deps;
        return $this;
    }

    /**
     * @return array|string[]
     */
    public function getDepsDev()
    {
        return $this->depsDev;
    }

    /**
     * @param array|string[] $depsDev
     * @return Monorepo
     */
    public function setDepsDev(array $depsDev)
    {
        $this->depsDev = $depsDev;
        return $this;
    }

    public function toArray()
    {

        $repo = [
            'name' => $this->name,
            'root' => $this->root,
            'deps' => $this->deps,
            'deps-dev' => $this->depsDev,
            'autoload' => $this->autoload->toArray(),
            'autoload-dev' => $this->autoloadDev->toArray(),
            'include-path' => $this->includePath,
            'bin' => $this->bin
        ];

        if (count($this->require)) {
            $this->require->ksort();
            $repo['require'] = $this->require->getArrayCopy();
        }

        if (count($this->requireDev)) {
            $this->requireDev->ksort();
            $repo['require-dev'] = $this->requireDev->getArrayCopy();
        }

        return $repo;
    }

}