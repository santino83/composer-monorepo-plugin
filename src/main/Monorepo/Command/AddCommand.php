<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 21/07/19
 * Time: 0.17
 */

namespace Monorepo\Command;


use Monorepo\Console;
use Monorepo\ContextBuilder;
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

        $context = ContextBuilder::create()
            ->withIo(new ConsoleIO($input, $output, $this->getHelperSet()))
            ->build(getcwd(), $optimize);

        $console = new Console();

        $root = $console->rootMonorepo($context);

        if (!$namespace) {
            $namespace = StringUtils::toNamespace($name);
            if (strpos($namespace, "\\") === FALSE) {
                $namespace = $root->getNamespace() . '\\' . $namespace;
            }
        }

        $request = new AddMonorepoRequest();
        $request->setNamespace($namespace)
            ->setPackageDir($packageDir)
            ->setName($name);

        if (!$noInteraction) {
            $proceed = $this->askInformations($request, $root, $input, $output);
            if (!$proceed) {
                return;
            }
        }else{
            if(!$packageDir){
                $packageDir = $root->getPackageDirs()[0] . DIRECTORY_SEPARATOR . StringUtils::toDirectoryPath($name);
                $request->setPackageDir($packageDir);
            }
        }

        $context->setRequest($request);
        $console->add($context);
    }

    /**
     * @param AddMonorepoRequest $request
     * @param Monorepo $root
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     */
    protected function askInformations(AddMonorepoRequest $request, Monorepo $root, InputInterface $input, OutputInterface $output)
    {
        $dependencyTree = DependencyTreeLoader::create()->load($root);

        $dependencies = array_merge(
            array_keys($dependencyTree->getDependencies()),
            array_keys($root->getRequire()->getArrayCopy()),
            array_keys($root->getRequireDev()->getArrayCopy())
        );

        $helper = $this->getHelper('question');
        /**@var $helper \Symfony\Component\Console\Helper\QuestionHelper */

        // ASK FOR NAMESPACE

        $dedaultNS = $request->getNamespace();

        $nsQuestion = new Question(sprintf("<info>Please enter the namespace of the monorepo %s:</info> ", $dedaultNS ? '[</info>' . $dedaultNS . '<info>]' : ''), $dedaultNS);
        $nsQuestion->setNormalizer(function ($value) {
            return strtolower(trim($value));
        });
        $nsQuestion->setValidator(function ($answer) {
            if (!is_string($answer) || !$answer) {
                throw new \RuntimeException('Please insert the namespace of the repo');
            }

            return StringUtils::toNamespace($answer);
        });
        $nsQuestion->setMaxAttempts(2);
        $request->setNamespace($helper->ask($input, $output, $nsQuestion));

        // ASK FOR PACKAGE DIR

        $dfPackageDir = $request->getPackageDir();
        if(!$dfPackageDir){
            $dfPackageDir = $root->getPackageDirs()[0].DIRECTORY_SEPARATOR.StringUtils::toDirectoryPath($request->getNamespace());
        }

        $pdQuestion = new Question(
            sprintf(
                "<info>Please enter the directory of the monorepo: %s</info> ",
                '[</info>' . $dfPackageDir . '<info>]'
            ),
            $dfPackageDir
        );
        $pdQuestion->setNormalizer(function ($string) {
            return trim($string);
        });
        $pdQuestion->setValidator(function ($answer) use ($root) {

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
        $pdQuestion->setMaxAttempts(6);


        $request->setPackageDir($helper->ask($input, $output, $pdQuestion));

        // ASK TO CHOOSE DEPENDENCIES

        $depQuestion = new ConfirmationQuestion('<info>Do you want to interactive add dependencies? [y/</info>N</info>]</info> ', false);
        if ($helper->ask($input, $output, $depQuestion)) {
            $this->chooseDependencies($request, $dependencies, $input, $output, false);
        }

        // ASK TO CHOOSE DEV DEPENDENCIES
        $depDevQuestion = new ConfirmationQuestion('<info>Do you want to interactive add development dependencies? [y/</info>N<info>]</info> ', false);
        if ($helper->ask($input, $output, $depDevQuestion)) {
            $this->chooseDependencies($request, $dependencies, $input, $output, true);
        }

        if (StringUtils::toPackageName($request->getNamespace()) !== StringUtils::toPackageName($request->getName())) {
            $nameQuestion = new ChoiceQuestion('<info>Please select the correct name for the monorepo: [</info>0<info>]</info>',
                [
                    StringUtils::toPackageName($request->getNamespace()),
                    StringUtils::toPackageName($request->getName())
                ],
                0
            );
            $request->setName($helper->ask($input, $output, $nameQuestion));
        } else {
            $request->setName(StringUtils::toPackageName($request->getName()));
        }

        // CONFIRM TO GENERATE
        $preview = json_encode([
            'name' => $request->getName(),
            'deps' => $request->getDeps(),
            'deps-dev' => $request->getDepsDev(),
            'autoload' => ['psr-4' => [$request->getNamespace() . '\\' => 'src' . DIRECTORY_SEPARATOR]]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $finalPath = $request->getPackageDir();

        $question = new ConfirmationQuestion(sprintf("<info>Do you want to create the following monorepo:</info> \n\n%s\n\n<info>into:</info> %s <info>? [</info>Y<info>/n]</info> ", $preview, $finalPath), true);
        return (bool)$helper->ask($input, $output, $question);
    }

    private function chooseDependencies(AddMonorepoRequest $request, array $dependencies, InputInterface $input, OutputInterface $output, $dev = false)
    {
        $choosed = [];

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
            $choosed[] = $answer;
        }

        if ($dev) {
            $request->setDepsDev($choosed);
        } else {
            $request->setDeps($choosed);
        }

    }
}