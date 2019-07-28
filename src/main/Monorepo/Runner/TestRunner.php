<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 28/07/19
 * Time: 17.44
 */

namespace Monorepo\Runner;


use Composer\IO\IOInterface;
use Composer\IO\NullIO;
use Monorepo\Model\Monorepo;

class TestRunner
{

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var string
     */
    private $currentCWD;

    /**
     * TestRunner constructor.
     * @param IOInterface $io
     */
    public function __construct(IOInterface $io = null)
    {
        $this->io = $io ?: new NullIO();
    }

    /**
     * @param Monorepo|Monorepo[] $targets
     * @param string $vendorDirName the configured vendor dir name (eg: vendor)
     */
    public function run($targets, $vendorDirName)
    {
        $this->currentCWD = getcwd();

        foreach((array)$targets as $target){
            $this->doRun($target, $vendorDirName);
        }

    }

    /**
     * Runs tests against the current monorepo
     *
     * @param Monorepo $monorepo
     * @param string $vendorDir
     */
    private function doRun(Monorepo $monorepo, $vendorDir)
    {
        $dir = dirname($monorepo->getPath());
        chdir($dir);

        $this->io->write('<info>Executing tests for package</info> '.$monorepo->getName());

        if(!$this->ensurePhpunitExists($dir, $vendorDir)){
            chdir($this->currentCWD);
            return;
        }

        try{
            $output = shell_exec(sprintf('%s -c .', $this->getPhpunitBinPath($vendorDir)));

            $parts = explode("\n", $output, 2); // removes the first line of the output (the header line)
            $this->io->write($parts[1]);

        }catch (\Exception $ex){
            $this->io->error($ex->getMessage());
        }

        chdir($this->currentCWD);
    }

    /**
     * Checks if phpunit is defined (config file and bin) for the current directory
     *
     * @param string $dir
     * @param string $vendorDir
     * @return bool
     */
    private function ensurePhpunitExists($dir, $vendorDir)
    {
        $found = false;
        foreach(['phpunit.xml','phpunit.xml.dist'] as $filename)
        {
            $found = $found || file_exists($dir.DIRECTORY_SEPARATOR.$filename);
        }

        if(!$found){
            $this->io->write('<comment>No phpunit config file found, skipping...</comment>');
            return false;
        }

        $phpunitBin = $this->getPhpunitBinPath($vendorDir);

        if(!file_exists($dir.DIRECTORY_SEPARATOR.$phpunitBin))
        {
            $this->io->write('<comment>no phpunit bin found, skipping ...</comment>');
            return false;
        }

        return true;
    }

    /**
     * @param string $vendorDir
     * @return string
     */
    private function getPhpunitBinPath($vendorDir)
    {
        return implode(DIRECTORY_SEPARATOR, [$vendorDir,'bin','phpunit']);
    }

}