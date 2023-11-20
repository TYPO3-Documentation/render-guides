<?php

declare(strict_types=1);

namespace T3Docs\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class Console extends Command
{
    public function __construct(
    ) {
        parent::__construct('run');

        $this->addArgument(
            'input',
            InputArgument::REQUIRED,
            'Path to directory where settings.cfg is stored, and guides.xml will be generated',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("Hello world\n");

        return Command::SUCCESS;
    }
}
