<?php
/**
 * Created by PhpStorm.
 * User: santino83
 * Date: 26/07/19
 * Time: 0.55
 */

namespace Monorepo\Command;


use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class BuildAllCommand extends BuildCommand
{
    protected function configure()
    {
        $this
            ->setDescription('Builds project\'s packages')
            ->setDefinition(array(
                new InputOption('build-version', null, InputOption::VALUE_OPTIONAL, 'Specifies the build version (default: auto-guess)')
            ));
    }

    /**
     * @inheritDoc
     */
    protected function processRequest($request, $input, $output)
    {
        $request->setBuildAll(true)
            ->setPackages([]);
    }

    /**
     * @inheritDoc
     */
    protected function getQuestion($request)
    {
        return new ConfirmationQuestion(
            sprintf('<info>Build all project\'s packages using version </info>%s<info>? [</info>Y<info>/n]</info> ',
                $request->getVersion()
            ), true);
    }

}