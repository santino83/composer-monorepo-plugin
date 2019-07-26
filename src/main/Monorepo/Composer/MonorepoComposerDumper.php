<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 25/07/19
 * Time: 21.17
 */

namespace Monorepo\Composer;


use Composer\Json\JsonFile;
use Monorepo\Model\Monorepo;

class MonorepoComposerDumper
{
    /**
     * @param Monorepo $monorepo
     * @param array $rootComposer
     * @param string $version the monorepo version
     * @param bool $forceVersion
     * @param bool $deleteOnError
     * @return bool
     */
    public function dump($monorepo, $rootComposer, $version = 'dev-master', $forceVersion = false, $deleteOnError = true)
    {
        $composerPath = dirname($monorepo->getPath()) . DIRECTORY_SEPARATOR . 'composer.json';

        $jsonComposer = new JsonFile($composerPath);

        $rawComposer = $this->getComposerFromMonorepo($monorepo, $rootComposer, $version, $forceVersion);

        try {
            $jsonComposer->write($rawComposer);
            $jsonComposer->validateSchema();

            if(!$jsonComposer->exists()){
                throw new \RuntimeException('Unable to create json file.');
            }

            return true;
        } catch (\Exception $ex) {

            if ($deleteOnError && $jsonComposer->exists()) {
                unlink($composerPath);
            }

            throw new \RuntimeException(sprintf('Unable to dump monorepo into %s : %s', $composerPath, $ex->getMessage()), $ex->getCode(), $ex);
        }
    }

    /**
     * @param Monorepo $monorepo
     * @param array $rootComposer
     * @param string $version
     * @param bool $forceVersion
     * @return array
     */
    private function getComposerFromMonorepo($monorepo, $rootComposer, $version, $forceVersion)
    {
        $raw = [
            'name' => $monorepo->getName(),
            'type' => $monorepo->getType(),
            'autoload' => $monorepo->getAutoload()->toArray(true),
            'autoload-dev' => $monorepo->getAutoloadDev()->toArray(true)
        ];

        if ($forceVersion) {
            $raw['version'] = $version;
        }

        if ($monorepo->getIncludePath()) {
            $raw['include-path'] = $monorepo->getIncludePath();
        }

        $allRequires = array_merge_recursive($monorepo->getRequire()->getArrayCopy(), $monorepo->getRequireDev()->getArrayCopy());

        if ($monorepo->getDeps()) {
            $raw['require'] = $this->getRequired($monorepo->getDeps(), $allRequires, $version);
        }

        if ($monorepo->getDepsDev()) {
            $raw['require-dev'] = $this->getRequired($monorepo->getDepsDev(), $allRequires, $version);
        }

        $excludeRootKeys = ['name', 'type', 'version', 'require', 'require-dev', 'autoload', 'autoload-dev', 'include-path', 'bin', 'vendor-dir'];
        $usedRootKeys = array_diff(array_keys($rootComposer), $excludeRootKeys);

        foreach ($usedRootKeys as $rootKey) {
            $raw[$rootKey] = $rootComposer[$rootKey];
        }

        if(!isset($raw['description'])){
            $raw['description'] = '';
        }

        if ($monorepo->getVendorDir() && $monorepo->getVendorDir() !== Monorepo::DEFAULT_VENDOR_DIR) {
            $raw['vendor-dir'] = $monorepo->getVendorDir();
        }

        if ($monorepo->getBin()) {
            $raw['bin'] = $monorepo->getBin();
        }

        return $raw;
    }

    /**
     * @param array $deps array of dependencies
     * @param array $require array of packageNames => packageVersion where to find dependencies
     * @param string $version the fixed version of missing dependencies (other monorepos)
     * @return array
     */
    private function getRequired(array $deps, array $require, $version)
    {
        $required = [];

        $vendorPackages = array_intersect(array_keys($require), $deps);
        $monorepoPackages = array_diff($deps, array_keys($require));

        foreach ($vendorPackages as $vendorPackage) {
            $required[$vendorPackage] = $require[$vendorPackage];
        }

        foreach ($monorepoPackages as $monorepoPackage) {
            $required[$monorepoPackage] = $version;
        }

        ksort($required);
        return $required;
    }

}