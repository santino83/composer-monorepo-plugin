<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 14/07/19
 * Time: 15.53
 */

namespace Monorepo\Composer\Util;

use Composer\Util\Filesystem as BaseFileSystem;

class Filesystem extends BaseFileSystem
{

    /**
     * @param mixed $paths, a list of paths to merge
     * @return string
     */
    public function path($paths)
    {
        $_paths = func_get_args();
        return self::doJoinPaths($_paths);
    }

    /**
     * @param array $paths
     * @return string
     * @see https://stackoverflow.com/a/7641174
     */
    private static function doJoinPaths(array $paths = [])
    {
        return preg_replace('~[/\\\\]+~', DIRECTORY_SEPARATOR, implode(DIRECTORY_SEPARATOR, $paths));
    }

}