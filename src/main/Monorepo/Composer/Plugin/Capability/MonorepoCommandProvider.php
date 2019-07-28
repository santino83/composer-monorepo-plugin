<?php

namespace Monorepo\Composer\Plugin\Capability;

use Monorepo\Command;
use Composer\Plugin\Capability\CommandProvider;

class MonorepoCommandProvider implements CommandProvider
{
    public function getCommands()
    {
        return [
            new Command\InitCommand('monorepo:init'),
            new Command\DumpAutoloadCommand('monorepo:dump-autoload'),
            new Command\AddCommand('monorepo:add'),
            new Command\BuildCommand('monorepo:build'),
            new Command\BuildAllCommand('monorepo:build-all'),
            new Command\TestCommand('monorepo:test'),
            new Command\TestAllCommand('monorepo:test-all')
           // new Command\GitChangedCommand('monorepo:git-changed?')
        ];
    }
}
