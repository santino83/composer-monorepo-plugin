<?php

namespace Monorepo\Composer;

use Monorepo\Build;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\Capability\CommandProvider;
use Monorepo\Context;
use Monorepo\ContextBuilder;

class Plugin implements PluginInterface, EventSubscriberInterface, Capable
{
    /**
     * @var \Monorepo\Build
     */
    private $build;

    /**
     * @var IOInterface
     */
    private $io;

    public function __construct(Build $build = null)
    {
        $this->build = $build;
    }

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->build = $this->build ?: new Build();
        $this->io = $io;
    }

    public static function getSubscribedEvents()
    {
        return [
            'post-autoload-dump' => 'generateMonorepoAutoloads',
        ];
    }

    /**
     * Delegate autoload dump to all the monorepo subdirectories.
     */
    public function generateMonorepoAutoloads(Event $event)
    {
        $flags = $event->getFlags();
        $optimize = isset($flags['optimize']) ? $flags['optimize'] : false;

        $context = ContextBuilder::create()
            ->withIo($this->io)
            ->build(getcwd(), $optimize, !$event->isDevMode());

        $this->build->build($context);
    }

    public function getCapabilities()
    {
        return [CommandProvider::class => MonorepoCommands::class];
    }
}
