<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 14/07/19
 * Time: 1.17
 */

namespace Monorepo\Loader;


use Composer\Composer;
use Composer\Config;
use Monorepo\Model\Autoload;
use Monorepo\Model\Monorepo;
use Monorepo\Schema\SchemaValidator;

class MonorepoLoader
{

    /**
     * @var SchemaValidator
     */
    private $validator;

    /**
     * @var ComposerLoader
     */
    private $composerLoader;

    /**
     * MonorepoLoader constructor.
     * @param SchemaValidator|null $validator
     * @param ComposerLoader|null $composerLoader
     */
    public function __construct($validator = null, $composerLoader = null)
    {
        $this->validator = $validator ? $validator : new SchemaValidator();
        $this->composerLoader = $composerLoader ? $composerLoader : new ComposerLoader();
    }

    /**
     * @param string|null|Composer $path to composer.json
     * @param bool $root
     * @return Monorepo
     */
    public function fromComposer($path = null, $root = false)
    {

        if($path && $path instanceof Composer)
        {
            $composer = $path;
        }else{
            $composer = $this->composerLoader->loadComposer($path);
        }

        $mr = new Monorepo($root);

        $package = $composer->getPackage();

        $mr->setName($package->getName())
            ->setVendorDir($composer->getConfig()->get('vendor-dir', Config::RELATIVE_PATHS));

        if($root) {

            // load require
            foreach ($package->getRequires() as $packageName => $config) {
                $mr->getRequire()[$packageName] = $config->getPrettyConstraint();
            }

            // load require-dev
            foreach ($package->getDevRequires() as $packageName => $config) {
                $mr->getRequireDev()[$packageName] = $config->getPrettyConstraint();
            }

            $mr->setRepositories($package->getRepositories());

        }else{

            // load deps
            $mr->setDeps(array_keys($package->getRequires()));

            // load deps-dev
            $mr->setDepsDev(array_keys($package->getDevRequires()));
        }

        // load autoload/dev-autoload/include-path/bin
        $mr->setAutoload(Autoload::fromArray($package->getAutoload()))
            ->setAutoloadDev(Autoload::fromArray($package->getDevAutoload()))
            ->setIncludePath($package->getIncludePaths())
            ->setBin($package->getBinaries())
            ->setExclude($package->getArchiveExcludes());

        return $mr;
    }

    /**
     * @param string $path monorepo.json path
     * @return Monorepo
     */
    public function load($path)
    {
        $raw = $this->fromJson(file_get_contents($path));

        $mr = new Monorepo($raw['root'], $path);
        $mr->setDepsDev($raw['deps-dev'])
            ->setDeps($raw['deps'])
            ->setBin($raw['bin'])
            ->setIncludePath($raw['include-path'])
            ->setName($raw['name'])
            ->setAutoloadDev(Autoload::fromArray($raw['autoload-dev']))
            ->setAutoload(Autoload::fromArray($raw['autoload']))
            ->setExclude($raw['exclude']);

        if($raw['vendor-dir']){
            $mr->setVendorDir($raw['vendor-dir']);
        }

        if($mr->isRoot()){

            if($raw['package-dirs']){
                $mr->setPackageDirs($raw['package-dirs']);
            }

            if($raw['build-dir']){
                $mr->setBuildDir($raw['build-dir']);
            }

            if($raw['namespace']){
                $mr->setNamespace($raw['namespace']);
            }

            if($raw['repositories']){
                $mr->setRepositories($raw['repositories']);
            }

            foreach($raw['require'] as $packageName => $packageVersion){
                $mr->getRequire()[$packageName] = $packageVersion;
            }

            foreach($raw['require-dev'] as $packageName => $packageVersion){
                $mr->getRequireDev()[$packageName] = $packageVersion;
            }

        }else{

            if($raw['type']){
                $mr->setType($raw['type']);
            }

            if($raw['package-vcs']){
                $mr->setPackageVcs($raw['package-vcs']);
            }

        }

        return $mr;
    }

    /**
     * TODO: change method visibility to protected and refactory (usage and tests)
     *
     * @param string $file full path to monorepo.json file
     * @return array
     * @throws \RuntimeException on errors
     */
    public function fromFile($file)
    {
        try{
            $content = file_get_contents($file);
            return $this->fromJson($content);
        }catch (\Exception $ex){
            throw new \RuntimeException(sprintf("Unable to parse %s : \n%s", $file, $ex->getMessage()));
        }
    }

    /**
     * TODO: change method visibility to protected and refactory (usage and tests)
     *
     * @param string $json the content of monorepo.json
     * @return array
     * @throws \RuntimeException on errors
     */
    public function fromJson($json)
    {
        $this->validator->validate($json);

        try{
            $monorepoJson = json_decode($json, true);
        }catch (\Exception $ex){
            $monorepoJson = NULL;
        }finally{
            if($monorepoJson === NULL){
                throw new \RuntimeException("Unable to parse given monorepo json");
            }

            return array_merge([
                'name' => '',
                'vendor-dir' => '',
                'build-dir' => '',
                'type' => '',
                'root' => false,
                'require' => [],
                'require-dev' => [],
                'autoload' => [],
                'autoload-dev' => [],
                'deps' => [],
                'deps-dev' => [],
                'include-path' => [],
                'bin' => [],
                'package-dirs' => [],
                'exclude' => [],
                'package-vcs' => [],
                'repositories' => [],
                'namespace' => null
            ],$monorepoJson);
        }
    }

}