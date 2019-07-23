<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 20/07/19
 * Time: 23.38
 */

namespace Monorepo\Composer;

use Composer\Package\Package as BasePackage;

class MonorepoPackage extends BasePackage
{

    /**
     * @var string
     */
    private $relativePathInstallation;

    /**
     * MonorepoPackage constructor.
     */
    public function __construct($name, $version, $prettyVersion)
    {
        parent::__construct($name, $version, $prettyVersion);
        $this->setType('monorepo');
    }

    /**
     * @return string
     */
    public function getRelativePathInstallation()
    {
        return $this->relativePathInstallation;
    }

    /**
     * @param string $relativePathInstallation
     * @return MonorepoPackage
     */
    public function setRelativePathInstallation($relativePathInstallation)
    {
        $this->relativePathInstallation = $relativePathInstallation;
        return $this;
    }

}