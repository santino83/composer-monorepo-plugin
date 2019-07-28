<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 28/07/19
 * Time: 18.11
 */

namespace Monorepo\Command;


use Composer\Command\BaseCommand;
use Composer\IO\ConsoleIO;
use Monorepo\Console;
use Monorepo\ContextBuilder;
use Monorepo\Request\TestMonorepoRequest;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TestAllCommand extends BaseCommand
{

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setDescription('Launches phpunit tests for all packages')
            ->setDefinition(array(
                new InputOption('dump-autoloader', 'D', InputOption::VALUE_NONE, 'Dump autoloaders before testing'),
                new InputOption('optimize-autoloader', 'o', InputOption::VALUE_NONE, 'Optimize autoloader during autoloader dump')
            ));
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $optimize = (bool)$input->getOption('optimize-autoloader');
        $dumpAutoloader = (bool)$input->getOption('dump-autoloader');

        $context = ContextBuilder::create()
            ->withIo(new ConsoleIO($input, $output, $this->getHelperSet()))
            ->withComposerConfig($this->getComposer()->getConfig())
            ->build(getcwd(), $optimize);

        $request = new TestMonorepoRequest();
        $request->setDumpAutoloaders($dumpAutoloader)
            ->setTestAll(true);

        $context->setRequest($request);

        $console = new Console();
        $console->test($context);
    }

}