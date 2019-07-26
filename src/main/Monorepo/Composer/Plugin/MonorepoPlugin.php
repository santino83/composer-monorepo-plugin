<?php

namespace Monorepo\Composer\Plugin;


use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\Capability\CommandProvider;
use Monorepo\Composer\Plugin\Capability\MonorepoCommandProvider;
use Monorepo\Console;
use Monorepo\ContextBuilder;

class MonorepoPlugin implements PluginInterface, EventSubscriberInterface, Capable
{

    /**
     * @var Console
     */
    private $console;

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * MonorepoPlugin constructor.
     * @param Console|null $console
     */
    public function __construct(Console $console = null)
    {
        $this->console = $console ?: new Console();
    }

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->console = $this->console ?: new Console();
        $this->io = $io;
    }

    public static function getSubscribedEvents()
    {
        return [
            'post-autoload-dump' => 'generateMonorepoAutoloads'
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

        $this->console->update($context);
    }

    public function getCapabilities()
    {
        return [CommandProvider::class => MonorepoCommandProvider::class];
    }
}
