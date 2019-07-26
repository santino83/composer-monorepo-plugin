<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 21/07/19
 * Time: 0.35
 */

namespace Monorepo\Request;


/**
 * Class AddMonorepoRequest
 * @package Monorepo\Request
 */
class AddMonorepoRequest extends AbstractMonorepoRequest
{

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $namespace;

    /**
     * @var string
     */
    private $packageDir;

    /**
     * @var array|string[]
     */
    private $deps = [];

    /**
     * @var array|string[]
     */
    private $depsDev = [];

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return AddMonorepoRequest
     */
    public function setName($name)
    {
        $this->name = $name;
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
     * @return AddMonorepoRequest
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * @return string
     */
    public function getPackageDir()
    {
        return $this->packageDir;
    }

    /**
     * @param string $packageDir
     * @return AddMonorepoRequest
     */
    public function setPackageDir($packageDir)
    {
        $this->packageDir = $packageDir;
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
     * @return AddMonorepoRequest
     */
    public function setDeps($deps)
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
     * @return AddMonorepoRequest
     */
    public function setDepsDev($depsDev)
    {
        $this->depsDev = $depsDev;
        return $this;
    }

}