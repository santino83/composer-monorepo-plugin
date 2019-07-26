<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 25/07/19
 * Time: 20.38
 */

namespace Monorepo\Request;


class BuildMonorepoRequest extends AbstractMonorepoRequest
{

    /**
     * @var array
     */
    private $packages = [];

    /**
     * @var bool
     */
    private $buildAll = false;

    /**
     * @var string
     */
    private $version = 'dev-master';

    /**
     * @return array
     */
    public function getPackages()
    {
        return $this->packages;
    }

    /**
     * @param array $packages
     * @return BuildMonorepoRequest
     */
    public function setPackages(array $packages)
    {
        $this->packages = $packages;
        return $this;
    }

    /**
     * @return bool
     */
    public function isBuildAll()
    {
        return $this->buildAll;
    }

    /**
     * @param bool $buildAll
     * @return BuildMonorepoRequest
     */
    public function setBuildAll($buildAll)
    {
        $this->buildAll = $buildAll;
        return $this;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param string $version
     * @return BuildMonorepoRequest
     */
    public function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }

}