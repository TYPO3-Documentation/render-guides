<?php

namespace T3Docs\GuidesExtension\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigureCommand extends Command
{
    public function __construct()
    {
        parent::__construct('typo3:configure');

        $this->addOption(
            'project-version',
            null,
            InputOption::VALUE_REQUIRED,
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $input->getOption('config') . '/guides.xml';
        $projectVersion = $input->getOption('project-version');

        $xml = simplexml_load_file($config);
        if ($projectVersion !== null) {
            $xml->xpath('/guides/project')[0]['version'] = $projectVersion;
        }
        $xml->asXML($config);

        return 0;
    }
}
