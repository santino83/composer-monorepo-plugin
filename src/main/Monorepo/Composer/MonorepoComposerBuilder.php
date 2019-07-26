<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 26/07/19
 * Time: 1.31
 */

namespace Monorepo\Composer;


use Composer\Json\JsonFile;
use Composer\Package\Archiver\ArchivableFilesFinder;
use Monorepo\Composer\Util\Filesystem;
use Monorepo\Model\Monorepo;

class MonorepoComposerBuilder
{

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var MonorepoComposerDumper
     */
    private $dumper;

    /**
     * MonorepoComposerBuilder constructor.
     * @param Filesystem|null $fs
     * @param MonorepoComposerDumper|null $dumper
     */
    public function __construct($fs = null, $dumper = null)
    {
        $this->fs = $fs ? $fs : new Filesystem();
        $this->dumper = $dumper ? $dumper : new MonorepoComposerDumper();
    }

    /**
     * @param Monorepo $monorepo
     * @param Monorepo $rootMonorepo
     * @param string $targetBasePath
     * @param string $version
     */
    public function build($monorepo, $rootMonorepo, $targetBasePath, $version)
    {
        $finalMonorepo = $this->combineMonorepos($monorepo, $rootMonorepo);
        $finalPath = $this->fs->path($targetBasePath, str_replace('/','-', $finalMonorepo->getName()));
        $finalMonorepo->setPath($this->fs->path($finalPath,'monorepo.json'));
        $sourcePath = realpath(dirname($monorepo->getPath()));

        $this->fs->removeDirectory($finalPath);
        if(!$this->cloneContents($sourcePath, $finalPath, $finalMonorepo->getExclude())){
            $this->fs->removeDirectory($finalPath);
            throw new \RuntimeException(sprintf('Unable to copy %s to %s', $sourcePath, $finalPath));
        }

        $rootComposerFile = new JsonFile($this->fs->path(dirname($rootMonorepo->getPath()), 'composer.json'));
        $rootComposer = $rootComposerFile->read();

        try{
            $this->dumper->dump($finalMonorepo, $rootComposer, $version, false, true);
        }catch (\RuntimeException $ex){
            $this->fs->removeDirectory($finalPath);
            throw $ex;
        }
    }

    /**
     * @param string $source
     * @param string $target
     * @param array|string[] $excludes
     * @return bool
     */
    private function cloneContents($source, $target, array $excludes = [])
    {
        $this->fs->ensureDirectoryExists($target);

        $result = true;

        $files = new ArchivableFilesFinder($source, $excludes);
        $baseSource = $this->fs->path($source, DIRECTORY_SEPARATOR);
        foreach ($files as $file) {
            /** @var \SplFileInfo $file */
            $targetPath = $this->fs->path($target, str_replace($baseSource, '', $file->getPathname()));
            if ($file->isDir()) {
                $this->fs->ensureDirectoryExists($targetPath);
            } else {
                $result = $result && copy($file->getPathname(), $targetPath);
            }
        }

        return $result;
    }

    /**
     * @param Monorepo $source
     * @param Monorepo $root
     * @return Monorepo
     */
    private function combineMonorepos($source, $root)
    {
        $monorepo = new Monorepo(true);
        $monorepo
            ->setName($source->getName())
            ->setType($source->getType())
            ->merge($root);

        $monorepo->merge($source);

        $excludes = array_merge(
            ['**/'.$monorepo->getVendorDir(),'**/monorepo.json'],
            $monorepo->getExclude()
        );

        if($source->isRoot()){
            $excludes[] = '/'.$source->getBuildDir();
        }

        $monorepo->setExclude(array_unique($excludes));

        return $monorepo;
    }

}