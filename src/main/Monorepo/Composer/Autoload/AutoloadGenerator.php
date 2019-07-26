<?php

namespace Monorepo\Composer\Autoload;

use Composer\Installer\InstallationManager;
use Composer\Package\PackageInterface;
use Composer\Autoload\AutoloadGenerator as BaseAutoloadGenerator;

class AutoloadGenerator extends BaseAutoloadGenerator
{
    public function buildPackageMap(InstallationManager $installationManager, PackageInterface $mainPackage, array $packages)
    {
        $packageMap = parent::buildPackageMap($installationManager, $mainPackage, $packages);

        $packageMap[0][1] = $installationManager->getInstallPath($mainPackage); // hack the install path

        return $packageMap;
    }

    protected function getAutoloadRealFile($useClassMap, $useIncludePath, $targetDirLoader, $useIncludeFiles, $vendorPathCode, $appBaseDirCode, $suffix, $useGlobalIncludePath, $prependAutoloader, $staticPhpVersion = 70000)
    {
        $file = parent::getAutoloadRealFile($useClassMap, $useIncludePath, $targetDirLoader, false, $vendorPathCode, $appBaseDirCode, $suffix, $useGlobalIncludePath, $prependAutoloader, $staticPhpVersion);

        if (! $useIncludeFiles) {
            return $file;
        }

        return $file .= <<<INCLUDE_FILES

\$includeFiles = require __DIR__ . '/autoload_files.php';
foreach (\$includeFiles as \$file) {
    composerRequireOnce$suffix(\$file);
}

function composerRequireOnce$suffix(\$file)
{
    require_once \$file;
}

INCLUDE_FILES;
    }
}