<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 21/07/19
 * Time: 19.27
 */

namespace Monorepo\Request;


use Monorepo\Util\StringUtils;

abstract class AbstractMonorepoRequest implements RequestInterface
{

    /**
     * @var string
     */
    private $requestName;

    /**
     * @inheritDoc
     */
    public function getRequestName()
    {
        if(!$this->requestName) {
            $rclass = new \ReflectionClass($this);
            $simpleName = $rclass->getShortName();
            $this->requestName = 'monorepo:'.StringUtils::toKebab(substr($simpleName,0, strlen($simpleName) - 15));
        }

        return $this->requestName;
    }


}