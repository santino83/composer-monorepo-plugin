<?php

namespace Monorepo\Utils;

/**
 * Class FileUtils
 * @package Monorepo\Utils
 */
class FileUtils
{

    /**
     * @param mixed $paths, a list of paths to merge
     * @return string
     * @see https://stackoverflow.com/a/7641174
     */
    public static function join_paths($paths)
    {
        $_paths = func_get_args();
        return self::doJoinPaths($_paths);
    }

    /**
     * @param string $dir
     * @param int $mode
     * @return bool
     */
    public static function create_dir($dir, $mode = 0777)
    {
        try{
            mkdir($dir, $mode, true);
        }catch (\Exception $ex){
            return false;
        }

        return self::file_exists($dir);
    }

    /**
     * @param mixed $paths, a list of paths to merge
     * @return null|string
     */
    public static function read_file($paths)
    {
        $file = self::doJoinPaths(func_get_args());
        return file_exists($file) && is_readable($file) ? trim(file_get_contents($file)) : null;
    }

    /**
     * @param mixed $paths, a list of paths to merge
     * @return bool
     */
    public static function is_writable($paths)
    {
        $file = self::doJoinPaths(func_get_args());
        return file_exists($file) && is_writeable($file);
    }

    /**
     * @param mixed $paths, a list of paths to merge
     * @return bool
     */
    public static function file_exists($paths)
    {
        $file = self::doJoinPaths(func_get_args());
        return file_exists($file);
    }

    /**
     * @param mixed $paths, a list of paths to merge
     * @return bool
     */
    public static function remove_file($paths)
    {
        $file = self::doJoinPaths(func_get_args());

        try {
            return unlink($file);
        }catch (\Exception $ex){
            return false;
        }
    }

    /**
     * @param array $files a list of files to delete
     * @return bool|mixed
     */
    public static function remove_files(array $files = [])
    {
        return !$files ? false : array_reduce($files, function($carry, $item){
            return $carry && self::remove_file($item);
        }, true);
    }

    /**
     * @param string $dir
     * @return bool
     */
    public static function remove_directory($dir)
    {
        try {

            $files = array_diff(scandir($dir), array('.', '..'));

            foreach ($files as $file) {
                $path = self::doJoinPaths([$dir, $file]);
                (is_dir($path)) ? self::remove_directory($path) : self::remove_file($path);
            }

            return rmdir($dir);

        }catch (\Exception $ex){
            return false;
        }
    }

    /**
     * @param string $file
     * @param string $content
     * @param bool $append
     * @return bool
     */
    public static function write($file, $content, $append = false)
    {
        return file_put_contents($file, $content, $append ? FILE_APPEND : 0) !== false;
    }

    /**
     * @param string $filePath
     * @param string $fileName
     * @param string $content
     * @param bool $append
     * @return bool
     */
    public static function write_file($filePath, $fileName, $content, $append = false)
    {
        return self::write(self::join_paths($filePath, $fileName), $content, $append);
    }

    /**
     * @param array $paths
     * @return string
     */
    private static function doJoinPaths(array $paths = [])
    {
        return preg_replace('~[/\\\\]+~', DIRECTORY_SEPARATOR, implode(DIRECTORY_SEPARATOR, $paths));
    }

}