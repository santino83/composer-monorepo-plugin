<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 27/07/19
 * Time: 12.37
 */

namespace Monorepo\Request;


class TestMonorepoRequest extends AbstractMonorepoRequest
{

    /**
     * @var bool
     */
    private $dumpAutoloaders = false;

    /**
     * @var bool
     */
    private $testAll = false;

    /**
     * @var string
     */
    private $package;

    /**
     * @return bool
     */
    public function isDumpAutoloaders()
    {
        return $this->dumpAutoloaders;
    }

    /**
     * @param bool $dumpAutoloaders
     * @return TestMonorepoRequest
     */
    public function setDumpAutoloaders($dumpAutoloaders)
    {
        $this->dumpAutoloaders = $dumpAutoloaders;
        return $this;
    }

    /**
     * @return bool
     */
    public function isTestAll()
    {
        return $this->testAll;
    }

    /**
     * @param bool $testAll
     * @return TestMonorepoRequest
     */
    public function setTestAll($testAll)
    {
        $this->testAll = $testAll;
        return $this;
    }

    /**
     * @return string
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * @param string $package
     * @return TestMonorepoRequest
     */
    public function setPackage($package)
    {
        $this->package = $package;

        if($this->package){
            $this->testAll = false;
        }else{
            $this->testAll = true;
        }

        return $this;
    }

}