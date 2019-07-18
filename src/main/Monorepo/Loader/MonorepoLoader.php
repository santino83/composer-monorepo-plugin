<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 14/07/19
 * Time: 1.17
 */

namespace Monorepo\Loader;


use Composer\Composer;
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

        $mr->setName($package->getName());

        if($root) {
            // load require
            foreach ($package->getRequires() as $packageName => $config) {
                $mr->getRequire()[$packageName] = $config->getPrettyConstraint();
            }

            // load require-dev
            foreach ($package->getDevRequires() as $packageName => $config) {
                $mr->getRequireDev()[$packageName] = $config->getPrettyConstraint();
            }
        }else{
            // load deps
            $deps = [];
            foreach ($package->getRequires() as $packageName => $config) {
                $deps[] = $packageName;
            }
            $mr->setDeps($deps);

            // load deps-dev
            $depsDev = [];
            foreach ($package->getDevRequires() as $packageName => $config) {
                $depsDev[] = $packageName;
            }
            $mr->setDepsDev($depsDev);
        }

        // load autoload/dev-autoload/include-path/bin
        $mr->setAutoload(Autoload::fromArray($package->getAutoload()))
            ->setAutoloadDev(Autoload::fromArray($package->getDevAutoload()))
            ->setIncludePath($package->getIncludePaths())
            ->setBin($package->getBinaries());

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
            ->setPackageDirs($raw['package-dirs'])
            ->setAutoloadDev(Autoload::fromArray($raw['autoload-dev']))
            ->setAutoload(Autoload::fromArray($raw['autoload']));

        if($mr->isRoot()){

            foreach($raw['require'] as $packageName => $packageVersion){
                $mr->getRequire()[$packageName] = $packageVersion;
            }

            foreach($raw['require-dev'] as $packageName => $packageVersion){
                $mr->getRequireDev()[$packageName] = $packageVersion;
            }

        }

        return $mr;
    }

    /**
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
                'root' => false,
                'require' => [],
                'require-dev' => [],
                'autoload' => [],
                'autoload-dev' => [],
                'deps' => [],
                'deps-dev' => [],
                'include-path' => [],
                'bin' => [],
                'package-dirs' => []
            ],$monorepoJson);
        }
    }

    /**
     * @return SchemaValidator
     */
    public function getValidator()
    {
        return $this->validator;
    }

}