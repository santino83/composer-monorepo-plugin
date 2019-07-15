<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 14/07/19
 * Time: 19.19
 */

namespace Monorepo\Command;


use Monorepo\ContextBuilder;
use Monorepo\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Composer\Command\BaseCommand;

class InitCommand extends BaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setDescription('Creates Monorepo-based project');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $context = ContextBuilder::create()->build(getcwd());

        $console = new Console();
        $console->init($context);
    }

}