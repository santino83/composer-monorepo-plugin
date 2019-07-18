<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 18/07/19
 * Time: 23.19
 */

namespace Monorepo\Dependency;


use Monorepo\Model\Monorepo;

class DependencyTree
{

    /**
     * @var string
     */
    private $root;

    /**
     * @var string
     */
    private $basePath;

    /**
     * @var array packageName => relative_path to base path
     */
    private $paths = [];

    /**
     * @var array pacakgeName => list of dependencies
     */
    private $deps = [];

    /**
     * DependencyTree constructor.
     * @param Monorepo $monorepoRoot the root monorepo
     */
    public function __construct($monorepoRoot)
    {
        $this->root = $monorepoRoot->getName();

        $this->basePath = dirname($monorepoRoot->getPath());
        if(substr($this->basePath, -1, 1) !== DIRECTORY_SEPARATOR){
            $this->basePath.= DIRECTORY_SEPARATOR;
        }


        $this->add($monorepoRoot);
    }

    /**
     * Adds a Monorepo to the tree
     * @param Monorepo $monorepo
     * @return DependencyTree
     */
    public function add($monorepo)
    {
        $this->deps[$monorepo->getName()] = [];
        $this->addPath($monorepo->getName(), $monorepo->getPath());

        foreach(array_merge($monorepo->getDeps(), $monorepo->getDepsDev()) as $dep)
        {
            $this->addDependency($monorepo->getName(), $dep);
        }

        return $this;
    }

    /**
     * Builds the tree
     * @return array
     */
    public function build()
    {
        // get only monorepos
        $deps = array_intersect_key($this->deps, $this->paths);

        foreach($deps as $key => &$dependsOn){
            $dependsOn = array_intersect($dependsOn, array_keys($this->paths));
        }

        // remove root
        unset($deps[$this->root]);

        // order by occurence found
        uasort($deps, function($a, $b){
            if($a && $b){ return 0; }
            if($a && !$b){ return 1; }
            return -1;
        });

        $tree = [];
        $this->append($this->root, $tree);

        foreach($deps as $package => $dependencies){

            if(!$dependencies){
                // depends only from root
                $this->append($package, $tree[$this->root]['children']);
                continue;
            }

            foreach($dependencies as $toSearch){
                $this->searchAndAppend($package, $toSearch, $tree[$this->root]);
            }

        }

        return $tree;
    }

    private function append($packageName, &$tree){
        $tree[$packageName] = [
            'path' => $this->paths[$packageName],
            'children' => []
        ];
    }

    private function addDependency($package, $dependsOn)
    {
        if(!array_key_exists($package, $this->deps)){
            $this->deps[$package] = [];
        }else{
            $this->deps[$package][] = $dependsOn;
        }
    }

    private function addPath($packageName, $path)
    {
        $relativePath = substr($path, strlen($this->basePath));
        $this->paths[$packageName] = $relativePath;
    }

    private function searchAndAppend($package, $search, &$subtree)
    {
        foreach($subtree['children'] as $key => &$treeChild){
            if($key === $search){
                $this->append($package, $treeChild['children']);
                return;
            }

            $this->searchAndAppend($package, $search, $treeChild);
        }
    }

}