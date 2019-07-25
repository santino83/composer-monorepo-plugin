<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 21/07/19
 * Time: 0.17
 */

namespace Monorepo\Command;


use Monorepo\Console;
use Monorepo\Context;
use Monorepo\ContextBuilder;
use Monorepo\Dependency\DependencyTree;
use Monorepo\Loader\DependencyTreeLoader;
use Monorepo\Model\Monorepo;
use Monorepo\Request\AddMonorepoRequest;
use Monorepo\Util\StringUtils;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Composer\IO\ConsoleIO;
use Composer\Command\BaseCommand;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class AddCommand extends BaseCommand
{

    /**
     * @var Console
     */
    private $console;

    /**
     * @var DependencyTree
     */
    private $dependencyTree;

    protected function configure()
    {
        $this
            ->setDescription('Add new Monorepo Package to project')
            ->setDefinition(array(
                new InputOption('optimize-autoloader', 'o', InputOption::VALUE_NONE, 'Optimize autoloader during autoloader dump'),
                new InputOption('namespace', null, InputOption::VALUE_OPTIONAL, 'New Monorepo Namespace. Required when no-interaction installation'),
                new InputOption('package-dir', null, InputOption::VALUE_OPTIONAL, 'Base Path where to install the new package. Required when no-interaction installation')
            ))
            ->addArgument('name', InputArgument::REQUIRED, 'The name of this package');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $optimize = (bool)$input->getOption('optimize-autoloader');

        $noInteraction = (bool)$input->getOption('no-interaction');
        $namespace = $input->getOption('namespace');
        $packageDir = $input->getOption('package-dir');
        $name = $input->getArgument('name');

        $this->console = new Console();

        $context = ContextBuilder::create()
            ->withIo(new ConsoleIO($input, $output, $this->getHelperSet()))
            ->withComposerConfig($this->getComposer()->getConfig())
            ->build(getcwd(), $optimize);

        $root = $this->console->rootMonorepo($context);
        $this->dependencyTree = DependencyTreeLoader::create()->load($root);

        $request = new AddMonorepoRequest();
        $request->setNamespace($namespace)
            ->setPackageDir($packageDir)
            ->setName($name);

        if($noInteraction){
            $this->executeNoInteraction($context, $request, $root, $input, $output);
            return;
        }

        $this->executeWithInteraction($context, $request, $root, $input, $output);
    }

    /**
     * Adds the new package with prompts for data
     *
     * @param Context $context
     * @param AddMonorepoRequest $request
     * @param Monorepo $root
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function executeWithInteraction(Context $context, AddMonorepoRequest $request, Monorepo $root, InputInterface $input, OutputInterface $output)
    {
        $this->askForNamespace($request, $root, $input, $output);

        $this->askForPackageDir($request, $root, $input, $output);

        $this->confirmPackageName($request, $input, $output);

        $this->checkExistance($request);

        $this->askForDependencies($request, $root, $input, $output);

        if($this->confirmGenerate($request, $input, $output))
        {
            $this->doExecute($context, $request);
        }
    }

    /**
     * Adds the new package without prompts for data
     *
     * @param Context $context
     * @param AddMonorepoRequest $request
     * @param Monorepo $root
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function executeNoInteraction(Context $context, AddMonorepoRequest $request, Monorepo $root, InputInterface $input, OutputInterface $output)
    {
        $request->setNamespace($this->getDefaultNS($request, $root))
            ->setPackageDir($this->getDefaultPackageDir($request, $root));

        if(!$request->getNamespace()){
            throw new \RuntimeException('Invalid namespace provided');
        }

        if(!$request->getPackageDir()){
            throw new \RuntimeException('Invalid package dir provided');
        }

        $this->checkExistance($request);

        $this->doExecute($context, $request);
    }

    /**
     * Adds the new package
     *
     * @param Context $context
     * @param AddMonorepoRequest $request
     */
    private function doExecute(Context $context, AddMonorepoRequest $request)
    {
        $context->setRequest($request);

        $this->console->add($context);
    }

    /**
     * Checks if doesn't exist another package with same name
     *
     * @param AddMonorepoRequest $request
     * @return bool
     */
    private function checkExistance(AddMonorepoRequest $request)
    {
        if($this->dependencyTree->has($request->getName())){
            throw new \RuntimeException(sprintf('Another package exists with name %s', $request->getName()));
        }

        return true;
    }

    /**
     * Confirms package generation
     *
     * @param AddMonorepoRequest $request
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     */
    private function confirmGenerate(AddMonorepoRequest $request, InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        /**@var $helper \Symfony\Component\Console\Helper\QuestionHelper */

        $preview = json_encode([
            'name' => $request->getName(),
            'deps' => $request->getDeps(),
            'deps-dev' => $request->getDepsDev(),
            'autoload' => ['psr-4' => [$request->getNamespace() . '\\' => 'src' . DIRECTORY_SEPARATOR]]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $finalPath = $request->getPackageDir();

        $question = new ConfirmationQuestion(
            sprintf("<info>Do you want to create the following monorepo:</info> \n\n%s\n\n<info>into:</info> %s <info>? [</info>Y<info>/n]</info> ", $preview, $finalPath),
            true);

        return (bool)$helper->ask($input, $output, $question);
    }

    /**
     * Confirms package name
     *
     * @param AddMonorepoRequest $request
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function confirmPackageName(AddMonorepoRequest $request, InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        /**@var $helper \Symfony\Component\Console\Helper\QuestionHelper */

        if (StringUtils::toPackageName($request->getNamespace()) !== StringUtils::toPackageName($request->getName())) {
            $question = new ChoiceQuestion('<info>Please select the correct name for the monorepo: [</info>0<info>]</info>',
                [
                    StringUtils::toPackageName($request->getNamespace()),
                    StringUtils::toPackageName($request->getName())
                ],
                0
            );
            $request->setName($helper->ask($input, $output, $question));
        } else {
            $request->setName(StringUtils::toPackageName($request->getName()));
        }
    }

    /**
     * Asks for Dependencies ( require and require-dev )
     *
     * @param AddMonorepoRequest $request
     * @param Monorepo $root
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function askForDependencies(AddMonorepoRequest $request, Monorepo $root, InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        /**@var $helper \Symfony\Component\Console\Helper\QuestionHelper */

        $dependencies = array_merge(
            array_keys($this->dependencyTree->getDependencies()),
            array_keys($root->getRequire()->getArrayCopy()),
            array_keys($root->getRequireDev()->getArrayCopy())
        );

        $depQuestion = new ConfirmationQuestion('<info>Do you want to interactive add dependencies? [y/</info>N</info>]</info> ', false);
        if ($helper->ask($input, $output, $depQuestion)) {
            $this->chooseDependencies($request, $dependencies, $input, $output, false);
        }

        // ASK TO CHOOSE DEV DEPENDENCIES
        $depDevQuestion = new ConfirmationQuestion('<info>Do you want to interactive add development dependencies? [y/</info>N<info>]</info> ', false);
        if ($helper->ask($input, $output, $depDevQuestion)) {
            $this->chooseDependencies($request, $dependencies, $input, $output, true);
        }

    }

    /**
     * Asks for Package Dir
     *
     * @param AddMonorepoRequest $request
     * @param Monorepo $root
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function askForPackageDir(AddMonorepoRequest $request, Monorepo $root, InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        /**@var $helper \Symfony\Component\Console\Helper\QuestionHelper */

        $dfPackageDir = $this->getDefaultPackageDir($request, $root);

        $question = new Question(
            sprintf(
                "<info>Please enter the directory of the monorepo: %s</info> ",
                '[</info>' . $dfPackageDir . '<info>]'
            ),
            $dfPackageDir
        );
        $question->setNormalizer(function ($string) {
            return trim($string);
        });
        $question->setValidator(function ($answer) use ($root) {

            $packageDirs = $root->getPackageDirs();

            if (!$answer) {
                throw new \RuntimeException('Please enter the directory of the monorepo');
            }

            if (in_array($answer, $packageDirs)) {
                throw new \RuntimeException('Monorepo directory must be in a subfolder of these folders: ' . implode(',', $packageDirs));
            }

            $regexps = array_map(function ($value) {
                return '@^' . preg_quote($value) . '[\\/]{1}.*$@i';
            }, $packageDirs);

            foreach ($regexps as $regexp) {
                if (preg_match($regexp, $answer)) {
                    return $answer;
                }
            }

            throw new \RuntimeException(sprintf('Monorepo directory must be in a subfolder of these folders: %s . Invalid path %s', implode(',', $packageDirs), $answer));
        });
        $question->setMaxAttempts(6);

        $request->setPackageDir($helper->ask($input, $output, $question));

    }

    /**
     * Asks for Namespace
     *
     * @param AddMonorepoRequest $request
     * @param Monorepo $root
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function askForNamespace(AddMonorepoRequest $request, Monorepo $root, InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        /**@var $helper \Symfony\Component\Console\Helper\QuestionHelper */

        $defaultNS = $this->getDefaultNS($request, $root);

        $question = new Question(sprintf("<info>Please enter the namespace of the monorepo %s:</info> ", $defaultNS ? '[</info>' . $defaultNS . '<info>]' : ''), $defaultNS);
        $question->setNormalizer(function ($value) {
            return strtolower(trim($value));
        });
        $question->setValidator(function ($answer) {
            if (!is_string($answer) || !$answer) {
                throw new \RuntimeException('Please insert the namespace of the repo');
            }

            return StringUtils::toNamespace($answer);
        });
        $question->setMaxAttempts(2);
        $request->setNamespace($helper->ask($input, $output, $question));
    }

    /**
     * Chooses Dependencies
     *
     * @param AddMonorepoRequest $request
     * @param array $dependencies
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param bool $dev
     */
    private function chooseDependencies(AddMonorepoRequest $request, array $dependencies, InputInterface $input, OutputInterface $output, $dev = false)
    {
        $choose = [];

        $helper = $this->getHelper('question');
        /**@var $helper \Symfony\Component\Console\Helper\QuestionHelper */

        $question = new Question('<info>Please enter the name of the required dependency:</info> ', null);
        $question->setNormalizer(function ($value) {
            if ($value !== null) {
                return strtolower(trim($value));
            }

            return $value;
        });
        $question->setValidator(function ($answer) use (&$dependencies) {
            if (!$answer) {
                return null;
            }

            if (!in_array($answer, $dependencies)) {
                throw new \RuntimeException(sprintf('Dependency %s doesn\'t exist in root monorepo project', $answer));
            }

            return $answer;
        });
        $question->setMaxAttempts(6);

        while (($answer = $helper->ask($input, $output, $question)) !== null) {
            $choose[] = $answer;
        }

        if ($dev) {
            $request->setDepsDev($choose);
        } else {
            $request->setDeps($choose);
        }

    }

    /**
     * Calculates default namespace based on the request
     *
     * @param AddMonorepoRequest $request
     * @param Monorepo $root
     * @return string
     */
    private function getDefaultNS(AddMonorepoRequest $request, Monorepo $root)
    {
        $defaultNS = $request->getNamespace();

        if (!$defaultNS) {
            $namespace = StringUtils::toNamespace($request->getName());

            if (strpos($namespace, "\\") === FALSE) {
                $defaultNS = $root->getNamespace() . '\\' . $namespace;
            }else{
                $defaultNS = $namespace;
            }
        }

        return $defaultNS;
    }

    /**
     * Calculates default package dir based on the request
     *
     * @param AddMonorepoRequest $request
     * @param Monorepo $root
     * @return string
     */
    private function getDefaultPackageDir(AddMonorepoRequest $request, Monorepo $root)
    {
        $dfPackageDir = $request->getPackageDir();

        if(!$dfPackageDir){
            $dfPackageDir = $root->getPackageDirs()[0].
                DIRECTORY_SEPARATOR.
                StringUtils::toDirectoryPath($request->getNamespace() ? $request->getNamespace() : $request->getName());
        }

        return $dfPackageDir;
    }

}