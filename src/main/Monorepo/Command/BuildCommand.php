<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 25/07/19
 * Time: 23.26
 */

namespace Monorepo\Command;


use Composer\Command\BaseCommand;
use Composer\IO\ConsoleIO;
use Monorepo\Console;
use Monorepo\ContextBuilder;
use Monorepo\Request\BuildMonorepoRequest;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class BuildCommand extends BaseCommand
{

    protected function configure()
    {
        $this
            ->setDescription('Builds project\'s packages')
            ->setDefinition(array(
                new InputOption('build-version', null, InputOption::VALUE_OPTIONAL, 'Specifies the build version (default: auto-guess)')
            ))
            ->addArgument('package', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'The package[ package[ ...]] to be build (separate multiple packages with a space)', []);
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $version = $input->getOption('build-version');
        $noInteraction = (bool)$input->getOption('no-interaction');

        $context = ContextBuilder::create()
            ->withIo(new ConsoleIO($input, $output, $this->getHelperSet()))
            ->withComposerConfig($this->getComposer()->getConfig())
            ->build(getcwd());

        $console = new Console();

        $useVersion = $version ? $version : $console->projectVersion($context);

        if (!$useVersion && !$version) {
            throw new \RuntimeException('Unable to guess the current version. Please enter a build version.');
        }

        $request = new BuildMonorepoRequest();
        $request->setVersion($useVersion);

        $this->processRequest($request, $input, $output);

        if (!$noInteraction) {
            $confirm = $this->askForConfirm($input, $output, $this->getQuestion($request));
            if (!$confirm) {
                return;
            }
        }

        $context->setRequest($request);
        $console->build($context);
    }

    /**
     * @param BuildMonorepoRequest $request
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function processRequest($request, $input, $output)
    {
        $packages = (array)$input->getArgument('package');

        $request->setBuildAll(false)
            ->setPackages($packages);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param Question $question
     * @return bool
     */
    protected function askForConfirm($input, $output, $question)
    {
        $helper = $this->getHelper('question');
        /**@var $helper \Symfony\Component\Console\Helper\QuestionHelper */

        return $helper->ask($input, $output, $question);
    }

    /**
     * @param BuildMonorepoRequest $request
     * @return Question
     */
    protected function getQuestion($request)
    {
        return new ConfirmationQuestion(
            sprintf('<info>Build package%s </info>%s<info> using version </info>%s<info>? [</info>Y<info>/n]</info> ',
                count($request->getPackages()) > 1 ? 's' : '',
                "\n\n   - " . implode("\n   - ", $request->getPackages()) . "\n\n",
                $request->getVersion()
            ), true);
    }

}