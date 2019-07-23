<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 14/07/19
 * Time: 18.53
 */

namespace Monorepo\Model;


use Monorepo\Util\StringUtils;

class Monorepo
{

    const DEFAULT_PACKAGE_DIRS = ['packages','lib'];
    const DEFAULT_VENDOR_DIR = 'vendor';

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
     * @var string
     */
    private $vendorDir = 'vendor';

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $namespace;

    /**
     * Monorepo constructor.
     * @param bool $root
     * @param string $path
     */
    public function __construct($root = false, $path = null)
    {
        $this->path = $path;
        $this->root = $root;
        $this->require = new Dependency();
        $this->requireDev = new Dependency();
        $this->autoload = new Autoload();
        $this->autoloadDev = new Autoload();
        $this->packageDirs = self::DEFAULT_PACKAGE_DIRS;
        $this->namespace = $path ? StringUtils::toPascal(basename(dirname($path))): null;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     * @return Monorepo
     */
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @return string
     */
    public function getVendorDir()
    {
        return $this->vendorDir;
    }

    /**
     * @param string $vendorDir
     * @return Monorepo
     */
    public function setVendorDir($vendorDir)
    {
        $this->vendorDir = $vendorDir;
        return $this;
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
        $this->packageDirs = $packageDirs ? $packageDirs : self::DEFAULT_PACKAGE_DIRS;
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

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @param string $namespace
     * @return Monorepo
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * Checks if $required is presents into requires
     *
     * @param $required
     * @param bool $includeDev checks in require-dev too
     * @return bool
     */
    public function hasRequire($required, $includeDev = true)
    {
        $result = $this->require->has($required);

        return $includeDev ? $result || $this->requireDev->has($required) : $result;
    }

    public function toArray()
    {
        $repo = [
            'name' => $this->name
        ];

        if($this->root){
            $repo['root'] = true;
        }

        if(!$this->autoload->isEmpty()){
            $repo['autoload'] = $this->autoload->toArray();
        }

        if(!$this->autoloadDev->isEmpty()){
            $repo['autoload-dev'] = $this->autoloadDev->isEmpty();
        }

        if($this->deps){
            $repo['deps'] = $this->deps;
        }

        if($this->depsDev){
            $repo['deps-dev'] = $this->depsDev;
        }

        if($this->includePath){
            $repo['include-path'] = $this->includePath;
        }

        if($this->bin){
            $repo['bin'] = $this->bin;
        }

        if(!$this->root){
            return $repo;
        }

        if($this->namespace){
            $repo['namespace'] = $this->namespace;
        }

        if($this->vendorDir && $this->vendorDir !== self::DEFAULT_VENDOR_DIR) {
            $repo['vendor-dir'] = $this->vendorDir;
        }

        if($this->packageDirs && count(array_diff($this->packageDirs, self::DEFAULT_PACKAGE_DIRS)) !== 0){
            $repo['package-dirs'] = $this->packageDirs;
        }

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

    /**
     * @param Monorepo $other
     * @return void
     */
    public function merge($other)
    {
        foreach(array_diff($other->getDeps(), $this->deps) as $dep){
            $this->deps[] = $dep;
        }

        foreach (array_diff($other->getDepsDev(), $this->depsDev) as $dep){
            $this->depsDev[] = $dep;
        }

        foreach(array_diff($other->getIncludePath(), $this->includePath) as $path){
            $this->includePath[] = $path;
        }

        foreach(array_diff($other->getBin(), $this->bin) as $path){
            $this->bin[] = $path;
        }

        if($this->root && $other->isRoot()){
            $this->requireDev = new Dependency();
            $this->require = new Dependency();

            foreach($other->getRequireDev() as $packageName => $packageVersion){
                $this->requireDev[$packageName] = $packageVersion;
            }

            foreach($other->getRequire() as $packageName => $packageVersion){
                $this->require[$packageName] = $packageVersion;
            }

            $this->namespace = $other->namespace ? $other->namespace : $this->namespace;
        }

        $this->vendorDir = $other->getVendorDir();

        $this->autoload = Autoload::fromArray($other->getAutoload()->toArray());
        $this->autoloadDev = Autoload::fromArray($other->getAutoloadDev()->toArray());
    }

}