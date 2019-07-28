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
     * @var Monorepo[] monorepoName => Monorepo
     */
    private $monorepos = [];

    /**
     * @var array monorepoName => missing dependencies
     */
    private $orphaned = [];

    /**
     * @var array the tree
     */
    private $tree;

    /**
     * @var bool
     */
    private $checkDepsDev;

    /**
     * DependencyTree constructor.
     * @param Monorepo $monorepoRoot the root monorepo
     * @param bool $checkDepsDev check dev dependencies
     */
    public function __construct($monorepoRoot, $checkDepsDev = true)
    {
        $this->root = $monorepoRoot->getName();
        $this->checkDepsDev = $checkDepsDev;

        $this->basePath = dirname($monorepoRoot->getPath());
        if(substr($this->basePath, -1, 1) !== DIRECTORY_SEPARATOR){
            $this->basePath.= DIRECTORY_SEPARATOR;
        }

        $this->add($monorepoRoot);
    }

    /**
     * @return Monorepo
     */
    public function getRoot()
    {
        return $this->monorepos[$this->root];
    }

    /**
     * Adds a Monorepo to the tree
     * @param Monorepo $monorepo
     * @return DependencyTree
     */
    public function add($monorepo)
    {
        $this->deps[$monorepo->getName()] = [];

        $this->monorepos[$monorepo->getName()] = $monorepo;
        $this->addPath($monorepo->getName(), $monorepo->getPath());

        foreach(array_merge($monorepo->getDeps(), $this->checkDepsDev ? $monorepo->getDepsDev() : []) as $dep)
        {
            $this->addDependency($monorepo->getName(), $dep);
        }

        // clear old tree
        $this->tree = [];

        return $this;
    }

    /**
     * @return bool
     */
    public function isCheckingDepsDev()
    {
        return $this->checkDepsDev;
    }

    /**
     * @return array
     */
    public function getTree()
    {
        $this->ensureBuilt();
        return $this->tree;
    }

    /**
     * @return array
     */
    public function getOrphaned()
    {
        $this->ensureBuilt();
        return $this->orphaned;
    }

    /**
     * @return bool
     */
    public function hasOrphaned()
    {
        $this->ensureBuilt();
        return count($this->orphaned) > 0;
    }

    /**
     * @param string $monorepoName
     * @return bool
     */
    public function has($monorepoName)
    {
        $this->ensureBuilt();
        return array_key_exists($monorepoName, $this->monorepos);
    }

    /**
     * @param string $monorepoName
     * @return Monorepo|null
     */
    public function get($monorepoName)
    {
        $this->ensureBuilt();
        return array_key_exists($monorepoName, $this->monorepos) ? $this->monorepos[$monorepoName] : null;
    }

    /**
     * @param bool $excludeRoot
     * @return array|string[]
     */
    public function getMonorepos($excludeRoot = false)
    {
        $this->ensureBuilt();
        $names = array_keys($this->monorepos);
        return $excludeRoot ? array_diff($names, [$this->root]) : $names;
    }

    /**
     * @param bool $excludeRoot default true
     * @return array $monorepoName => $dependencies string[]
     */
    public function getDependencies($excludeRoot = true)
    {
        $this->ensureBuilt();
        $deps = array_merge([], $this->deps);

        if($excludeRoot){
            unset($deps[$this->root]);
        }

        return $deps;
    }

    /**
     * Builds the tree
     */
    public function build()
    {

        // get only monorepos
        $deps = array_intersect_key($this->deps, $this->paths);

        $this->checkDependencies($deps);

        foreach($deps as $key => &$dependsOn){
            $dependsOn = array_intersect($dependsOn, array_keys($this->paths));
        }

        // remove root
        unset($deps[$this->root]);

        // order by occurence found
        uksort($deps, function($ka, $kb) use($deps){

            $va = $deps[$ka];
            $vb = $deps[$kb];

            if(!$va && !$vb){
                // no dependencies
                return 0;
            }

            // circular dependencies check
            if(in_array($ka, $vb) && in_array($kb, $va)){
                throw new \RuntimeException(
                    sprintf('Circular dependencies found: %s and %s depends each others', $ka, $kb)
                );
            }

            if(in_array($ka, $vb)){
                //b depends on a, before a
                return -1;
            }

            if(in_array($kb, $va)){
                //a depends on b, before b
                return 1;
            }

            if(!$va && $vb){
                //a has no dependencies instead of b, before a
                return -1;
            }

            if(!$vb && $va){
                //b has no dependencies instead of a, before b
                return 1;
            }

            // default: are equals
            return 0;
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

        $this->tree = $tree;
    }

    /**
     * Iterates through all founded monorepoNames => dependencies and
     * check dependencies status, filling up orphaned array when a dependency is missing
     *
     * @param array $monorepos
     */
    private function checkDependencies(array $monorepos)
    {
        $rootMonorepo = $this->monorepos[$this->root];

        foreach($monorepos as $monorepoName => $dependencies){
            if($monorepoName === $rootMonorepo->getName()){
                continue;
            }

            foreach($dependencies as $dependency){

                if(array_key_exists($dependency, $monorepos) || self::isMetaDependency($dependency)){
                    continue;
                }

                if(!$rootMonorepo->hasRequire($dependency, $this->checkDepsDev)){
                    $this->addOrphaned($monorepoName, $dependency);
                }

            }
        }

    }

    // TODO: move isMetaDependency in an Util Class
    /**
     * @param string $dependency
     * @return bool
     */
    public static function isMetaDependency($dependency)
    {
        return $dependency === 'php' ||
                strpos($dependency, 'ext-') === 0 ||
                strpos($dependency, 'lib-') === 0 ||
                $dependency === 'composer-plugin-api';
    }

    /**
     * Builds the tree if it's not built
     */
    private function ensureBuilt()
    {
        if(!$this->tree){
            $this->build();
        }
    }

    /**
     * @param string $packageName
     * @param string $dependency
     */
    private function addOrphaned($packageName, $dependency)
    {
        if(!array_key_exists($packageName, $this->orphaned)){
            $this->orphaned[$packageName] = [];
        }

        $this->orphaned[$packageName][] = $dependency;
    }

    private function append($packageName, &$tree){
        $tree[$packageName] = [
            'config' => $this->monorepos[$packageName],
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