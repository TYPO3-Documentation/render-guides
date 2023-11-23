<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Command;

use PHPStan\Command\Output;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ConfigureCommand extends Command
{
    protected static $defaultName = 'configure';

    /** @var \DOMDocument Holds the XML document that will be written (guides.xml) */
    private \DOMDocument $xmlDocument;

    protected function configure(): void
    {
        $this->setDescription('Configure guides.xml attributes programmatically.');
        $this->setHelp(
            <<<'EOT'
                    The <info>%command.name%</info> command helps to set configuration options
                    and attributes of the project to be rendered. These options are saved
                    in a file <info>guides.xml</info>.
                    You can use this CLI instead of manually editing the xml file.

                    <info>$ php %command.name% [parameters]</info>

                    EOT
        );
        $this->setDefinition([
            new InputOption(
                'project-version',
                null,
                InputOption::VALUE_OPTIONAL,
                'Set project version (<info>guides.project[version]</info>)'
            ),
            new InputOption(
                'project-title',
                null,
                InputOption::VALUE_OPTIONAL,
                'Set project title (<info>guides.project[title]</info>)'
            ),
            new InputOption(
                'project-release',
                null,
                InputOption::VALUE_OPTIONAL,
                'Set project title (<info>guides.project[release]</info>)'
            ),
            new InputOption(
                'project-copyright',
                null,
                InputOption::VALUE_OPTIONAL,
                'Set project title (<info>guides.project[copyright]</info>)'
            ),

            new InputArgument(
                'input',
                InputArgument::OPTIONAL,
                'Path to directory where <info>guides.xml</info> will be modified/created.',
                './'
            ),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = $input->getArgument('input') . '/guides.xml';

        if (!file_exists($config)) {
            if (!$this->createEmptyGuides($config, $output)) {
                return Command::FAILURE;
            }
        }

        if (!$this->operateOnXml($config, $input, $output)) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function operateOnXml(string $config, InputInterface $input, OutputInterface $output): bool
    {
        $xml = simplexml_load_file($config);
        if ($xml === false) {
            $output->writeln(sprintf('<error>Could not parse %s as XML</error>', $config));
            return false;
        }

        // Register the namespace
        $xml->registerXPathNamespace('ns', 'https://www.phpdoc.org/guides');

        $guides = $xml->xpath('/ns:guides');
        if ($guides === []) {
            $output->writeln('<error>Malformed file, missing root "guides" XML element.');
            return false;
        }

        $projectVariables = [
            'version' => $input->getOption('project-version'),
            'release' => $input->getOption('project-release'),
            'title' => $input->getOption('project-title'),
            'copyright' => $input->getOption('project-copyright'),
        ];

        // TODO: Add other elements, see XSD ./vendor/phpdocumentor/guides-cli/resources/schema/guides.xsd
        // guides.inventory [id, url]
        // guides.theme [extends]
        // guides.theme.template [file, node, format]
        // guides.extension [class, any]
        // guides.base-template-path
        // guides.output-format
        // guides[input, input-file, output, input-format, log-path, fail-on-log, show-progress, theme, default-code-language, links-are-relative

        $projectElement = null;
        foreach ($projectVariables as $projectAttribute => $projectValue) {
            if ($projectValue === null) {
                continue;
            }

            // To be discussed
            if (!is_string($projectValue)) {
                continue;
            }

            if ($projectElement === null) {
                $elements = $xml->xpath('/ns:guides/ns:project');
                if ($elements === []) {
                    if ($output->isVerbose()) {
                        $output->writeln('Created <info>guides.project</info> XML root');
                    }
                    $projectElement = $xml->addChild('project');
                } else {
                    $projectElement = $elements[0];
                }
            }

            if ($output->isVerbose()) {
                $output->writeln(sprintf('Setting <info>guides.project[%s]</info> = <info>%s</info>', $projectAttribute, $projectValue));
            }

            $projectElement[$projectAttribute] = $projectValue;
        }

        $xml->asXML($config);

        echo file_get_contents($config) . "\n";

        return true;
    }

    private function createEmptyGuides(string $config, OutputInterface $output): bool
    {
        if (!is_dir(dirname($config))) {
            $output->writeln(sprintf('<error>Cannot create guides.xml in missing directory "%s"</error>', dirname($config)));
            return false;
        }

        $fp = fopen($config, 'w');
        if (!$fp) {
            $output->writeln(sprintf('<error>Could not create %s</error>', $config));
            return false;
        }

        $this->xmlDocument = new \DOMDocument('1.0', 'UTF-8');
        $this->xmlDocument->preserveWhiteSpace = true;
        $this->xmlDocument->formatOutput = true;

        $guides = $this->xmlDocument->createElement('guides');

        // Defaults
        $guides->setAttribute('xmlns', 'https://www.phpdoc.org/guides');
        $guides->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $guides->setAttribute('xsi:schemaLocation', 'https://www.phpdoc.org/guides ../vendor/phpdocumentor/guides-cli/resources/schema/guides.xsd');
        $guides->setAttribute('links-are-relative', 'true');

        $this->xmlDocument->appendChild($guides);

        fwrite($fp, (string)$this->xmlDocument->saveXML());

        if ($output->isVerbose()) {
            $output->writeln(sprintf('<info>%s</info> created.', $config));
        }

        return true;
    }
}
