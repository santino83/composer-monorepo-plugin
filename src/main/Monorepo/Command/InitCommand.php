<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 14/07/19
 * Time: 19.19
 */

namespace Monorepo\Command;


use Composer\IO\ConsoleIO;
use Monorepo\ContextBuilder;
use Monorepo\Console;
use Monorepo\Request\InitiMonorepoRequest;
use Monorepo\Util\StringUtils;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Composer\Command\BaseCommand;
use Symfony\Component\Console\Question\Question;

class InitCommand extends BaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setDescription('Creates Monorepo-based project')
            ->setDefinition([
                new InputOption('optimize-autoloader', 'o', InputOption::VALUE_NONE, 'Optimize autoloader during autoloader dump'),
                new InputOption('namespace', null, InputOption::VALUE_OPTIONAL, 'Global base namespace for the project')
            ]);
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $optimize = (bool)$input->getOption('optimize-autoloader');
        $noInteraction = (bool)$input->getOption('no-interaction');

        $namespace = $input->getOption('namespace');

        if(!$namespace){

            if($noInteraction){
               throw new \RuntimeException('Please enter the base namespace');
            }

            $currentBase = StringUtils::toPascal(basename(getcwd()));
            $question = new Question(sprintf('<info>Please insert the global base namespace for the project: [ %s ]</info> ', $currentBase), $currentBase);
            $question->setNormalizer(function($value){
               return trim($value);
            });
            $question->setValidator(function($answer){
                if(!is_string($answer) || !$answer){
                    throw new \RuntimeException('Please insert the namespace');
                }

                return StringUtils::toNamespace($answer);
            });
            $question->setMaxAttempts(3);

            $namespace = $this->getHelper('question')->ask($input, $output, $question);
        }

        $request = new InitiMonorepoRequest();
        $request->setNamespace($namespace);

        $context = ContextBuilder::create()
            ->withIo(new ConsoleIO($input, $output, $this->getHelperSet()))
            ->withComposerConfig($this->getComposer()->getConfig())
            ->withRequest($request)
            ->build(getcwd(), $optimize);

        $console = new Console();
        $console->init($context);
    }

}