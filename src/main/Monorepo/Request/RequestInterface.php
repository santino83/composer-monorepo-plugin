<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 21/07/19
 * Time: 0.35
 */

namespace Monorepo\Request;


interface RequestInterface
{

    /**
     * Returns the name of the request
     *
     * @return string
     */
    public function getRequestName();

}