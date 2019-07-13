<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 14/07/19
 * Time: 0.51
 */

namespace Monorepo;


use Composer\IO\NullIO;

class ContextBuilderTest extends \PHPUnit_Framework_TestCase
{

    public function testCreateDefaults()
    {
        $rootDir = getcwd();
        $context = ContextBuilder::create()->build($rootDir, true, true);

        $this->assertNotNull($context->getGenerator());
        $this->assertNotNull($context->getInstallationManager());
        $this->assertNotNull($context->getIo());
        $this->assertEquals($rootDir, $context->getRootDirectory());
        $this->assertTrue($context->isOptimize());
        $this->assertTrue($context->isNoDevMode());
        $this->assertTrue($context->getIo() instanceof NullIO);
    }

    public function testCloneFromContext()
    {
        $rootDir = getcwd();
        $source = ContextBuilder::create()->build($rootDir, true, true);

        $context = ContextBuilder::cloneFromContext($source);

        $this->assertNotNull($context->getGenerator());
        $this->assertNotNull($context->getInstallationManager());
        $this->assertNotNull($context->getIo());
        $this->assertEquals($rootDir, $context->getRootDirectory());
        $this->assertTrue($context->isOptimize());
        $this->assertTrue($context->isNoDevMode());
        $this->assertTrue($context->getIo() instanceof NullIO);

        $context->setIo(null);

        $this->assertNull($context->getIo());
        $this->assertNotNull($source->getIo());
    }

}