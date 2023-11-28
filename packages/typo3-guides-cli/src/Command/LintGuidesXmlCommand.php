<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use T3Docs\GuidesCli\XmlValidator;

final class LintGuidesXmlCommand extends Command
{
    protected static $defaultName = 'lint-guides-xml';

    private string $xsdSchema = './vendor/phpdocumentor/guides-cli/resources/schema/guides.xsd';

    protected function configure(): void
    {
        $this->setDescription('Validates all guides.xml settings files.');
        $this->setHelp(
            <<<'EOT'
                The <info>%command.name%</info> command iterates all found files
                called <info>guides.xml</info> and checks the for XSD conformity.

                <info>$ php %command.name% [parameters]</info>

                EOT
        );
        $this->setDefinition([
            new InputArgument(
                'input',
                InputArgument::OPTIONAL,
                'Path to the root directory, where guides.xml files are collected from. Traverses subdirectories.',
                './'
            ),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $baseDirectory = $input->getArgument('input');
        if (!is_string($baseDirectory)) {
            $baseDirectory = './';
        }

        if ($output->isVerbose()) {
            $output->writeln(sprintf('<info>Finding guides.xml</info> in directory tree below <info>%s</info>', $baseDirectory));
        }

        $files = $this->gatherFiles($baseDirectory);

        if ($output->isVerbose()) {
            $output->writeln(sprintf('Got <info>%d</info> files.', count($files)));
        }

        if (!$this->lintFiles($output, $files)) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * @param array<int, string> $files
     */
    private function lintFiles(OutputInterface $output, array $files): bool
    {
        foreach ($files as $file) {
            $validator = new XmlValidator($file, $this->xsdSchema);
            if (!$validator->validate()) {
                if ($output->isVerbose()) {
                    $validator->showErrors($output);
                } else {
                    $output->writeln(sprintf('<error>%s</error> failed to be validated against XSD schema (use --verbose to see why).', $file));
                }

                return false;
            }
            $output->writeln(sprintf('+ <info>%s</info> validates.', $file));
        }

        return true;
    }

    /**
     * @param string $baseDirectory
     * @return array<int, string>
     */
    private function gatherFiles(string $baseDirectory): array
    {
        $finder = new Finder();

        $finder
            ->in($baseDirectory)
            ->files()
            ->name('guides.xml');

        $files = [];
        foreach ($finder as $file) {
            $files[] = $file->getRealPath();
        }

        return $files;
    }
}
