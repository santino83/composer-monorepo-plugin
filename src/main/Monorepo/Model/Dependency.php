<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 14/07/19
 * Time: 18.56
 */

namespace Monorepo\Model;

use ArrayObject;

class Dependency extends ArrayObject
{

    /**
     * Dependency constructor.
     */
    public function __construct()
    {
        parent::__construct([], self::ARRAY_AS_PROPS);
    }

    /**
     * @param $packageName
     * @return bool
     */
    public function has($packageName)
    {
        return $this->offsetExists($packageName);
    }

}