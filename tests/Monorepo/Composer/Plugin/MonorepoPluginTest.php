<?php

namespace Monorepo\Composer\Plugin;


use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Monorepo\Console;
use Monorepo\ContextBuilder;

class MonorepoPluginTest extends \PHPUnit_Framework_TestCase
{
    public function testOnPostAutoloadDump()
    {

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
        $plugin = new MonorepoPlugin($console);
        $plugin->generateMonorepoAutoloads($event);

        $context = ContextBuilder::create()->build(getcwd(), false, true);

        \Phake::verify($console)->update($context);
    }
}
