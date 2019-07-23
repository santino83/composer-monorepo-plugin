<?php

namespace Monorepo\Composer;

use Monorepo\Command;
use Composer\Plugin\Capability\CommandProvider;

class MonorepoCommands implements CommandProvider
{
    public function getCommands()
    {
        return [
            new Command\InitCommand('monorepo:init'),
            new Command\BuildCommand('monorepo:build'),
            new Command\AddCommand('monorepo:add')
           // new Command\GitChangedCommand('monorepo:git-changed?')
        ];
    }
}
