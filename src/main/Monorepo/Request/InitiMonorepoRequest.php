<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 21/07/19
 * Time: 19.27
 */

namespace Monorepo\Request;


class InitiMonorepoRequest extends AbstractMonorepoRequest
{

    /**
     * @var string
     */
    private $namespace;

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @param string $namespace
     * @return InitiMonorepoRequest
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }

}