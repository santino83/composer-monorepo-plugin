<?php

namespace Monorepo\Composer;

use Monorepo\Build;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Monorepo\Console;
use Monorepo\Context;
use Monorepo\ContextBuilder;

class PluginTest extends \PHPUnit_Framework_TestCase
{
    public function testOnPostAutoloadDump()
    {
        $build = \Phake::mock(Build::class);
        $composer = \Phake::mock(Composer::class);
        $io = \Phake::mock(IOInterface::class);
        $console = \Phake::mock(Console::class);

        $event = new Event(
            'post-autoload-dump',
            $composer,
            $io,
            false, // dev-mode
            [], // args
            ['optimize' => false] // flags
        );
        $plugin = new Plugin($build, $console);
        $plugin->generateMonorepoAutoloads($event);

        $context = ContextBuilder::create()->build(getcwd(), false, true);

        \Phake::verify($console)->update($context);
        \Phake::verify($build)->build($context);
    }
}
