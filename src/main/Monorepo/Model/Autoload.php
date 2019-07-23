<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 08/07/19
 * Time: 17.32
 */

namespace Monorepo\Model;


use ArrayObject;

class Autoload
{

    /**
     * @var ArrayObject
     */
    private $psr0;

    /**
     * @var ArrayObject
     */
    private $psr4;

    /**
     * @var string[]
     */
    private $classmap = [];

    /**
     * @var string[]
     */
    private $files = [];

    /**
     * Autoload constructor.
     */
    public function __construct()
    {
        $this->psr0 = new ArrayObject([], ArrayObject::STD_PROP_LIST);
        $this->psr4 = new ArrayObject([], ArrayObject::STD_PROP_LIST);
    }

    /**
     * @param array $source
     * @return Autoload
     */
    public static function fromArray(array $source)
    {
        $instance = new self();

        if (isset($source['classmap'])) {
            $instance->setClassmap((array)$source['classmap']);
        }

        if (isset($source['files'])) {
            $instance->setFiles((array)$source['files']);
        }

        if (isset($source['psr-0'])) {
            foreach ((array)$source['psr-0'] as $namespace => $directory) {
                $instance->getPsr0()[$namespace] = $directory;
            }
        }

        if (isset($source['psr-4'])) {
            foreach ((array)$source['psr-4'] as $namespace => $directory) {
                $instance->getPsr4()[$namespace] = $directory;
            }
        }

        return $instance;
    }

    /**
     * @return ArrayObject
     */
    public function getPsr0()
    {
        return $this->psr0;
    }

    /**
     * @return ArrayObject
     */
    public function getPsr4()
    {
        return $this->psr4;
    }

    public function isEmpty()
    {
        return !$this->classmap &&
            !$this->files &&
            $this->psr0->count() === 0 &&
            $this->psr4->count() === 0;
    }

    /**
     * @return string[]
     */
    public function getClassmap()
    {
        return $this->classmap;
    }

    /**
     * @param string[] $classmap
     * @return Autoload
     */
    public function setClassmap(array $classmap = [])
    {
        $this->classmap = $classmap;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @param string[] $files
     * @return Autoload
     */
    public function setFiles(array $files = [])
    {
        $this->files = $files;
        return $this;
    }

    /**
     * Converts this object into an array
     *
     * @param bool $useObjectsOnEmpty
     * @return array|\stdClass
     */
    public function toArray($useObjectsOnEmpty = false)
    {
        if($this->isEmpty() && $useObjectsOnEmpty){

            return new \stdClass();
        }

        if($this->isEmpty()){
            return ['classmap' => []];
        }

        $return = [];

        if($this->classmap){
            $return['classmap'] = $this->classmap;
        }

        if($this->files){
            $return['files'] = $this->files;
        }

        if ($this->psr0->count() > 0) {
            foreach ($this->psr0 as $namespace => $directory) {
                $return['psr-0'][$namespace] = $directory;
            }
        }

        if ($this->psr4->count() > 0) {
            foreach ($this->psr4 as $namespace => $directory) {
                $return['psr-4'][$namespace] = $directory;
            }
        }

        return $return;
    }

}