<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use T3Docs\VersionHandling\Packagist\ComposerPackage;
use T3Docs\VersionHandling\Packagist\PackagistService;

final class InitCommand extends Command
{
    protected static $defaultName = 'init';

    protected function configure(): void
    {
        $this->setDescription('Initialize a new documentation project');
        $this->addOption('working-dir', 'w', InputOption::VALUE_REQUIRED, 'If specified, use the given directory as working directory.');
        $this->setHelp(
            <<<'HELP'
                This interactive command will help you to setup your project documentation.
                To do so, it will ask you a few questions about your project and then
                create a new documentation project in the working directory (default: current directory).

                For more information, see:
                https://docs.typo3.org/permalink/h2document:basic-principles
                HELP
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('working-dir')) {
            $workdir = $input->getOption('working-dir');
            assert(is_string($workdir));

            if (chdir($workdir)) {
                $output->writeln('<info>Changed working directory to ' . getcwd() . '</info>');
            } else {
                $output->writeln('<error>Could not change working directory to ' . $workdir . '</error>');
                return Command::INVALID;
            }
        }

        if ($input->getOption('quiet')) {
            echo 'This command is interactive and requires user input.' . PHP_EOL;
            return Command::INVALID;
        }

        if (file_exists('Documentation/guides.xml')) {
            $output->writeln('<error>A "Documentation" directory already exists in this directory</error>');
            return Command::INVALID;
        }

        $output->writeln('Welcome to the <comment>TYPO3 documentation</comment> project setup wizard');
        $output->writeln('This wizard will help you to create a new documentation project in the current directory.');
        $output->writeln('');

        $composerInfo = $this->getComposerInfo($output);

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $projectName = $helper->ask($input, $output, new Question(sprintf('What is the name of your project? <comment>[%s]</comment>: ', $composerInfo?->getComposerName()), $composerInfo?->getComposerName()));

        $homepageQuestion = new Question(sprintf('What is the URL of your project\'s homepage? <comment>[%s]</comment>: ', $composerInfo?->getHomepage()), $composerInfo?->getHomepage());
        $homepageQuestion->setAutocompleterValues([
            'https://extensions.typo3.org/extension/' . $composerInfo?->getComposerName(),
            'https://extensions.typo3.org/package/' . $composerInfo?->getComposerName(),
            $composerInfo?->getHomepage(),
        ]);

        $homepageQuestion->setValidator(function ($answer) {
            if (!filter_var($answer, FILTER_VALIDATE_URL)) {
                throw new \RuntimeException('The URL is not valid');
            }
            return $answer;
        });

        $projectHomePage = $helper->ask($input, $output, $homepageQuestion);

        $repositoryQuestion = new Question(sprintf('What is the URL of your project\'s repository? '));
        $repositoryQuestion->setAutocompleterValues([
            'https://github.com/' . $composerInfo?->getComposerName(),
            'https://gitlab.com/' . $composerInfo?->getComposerName(),
            $composerInfo?->getHomepage(),
        ]);

        $repositoryUrl = $helper->ask($input, $output, $repositoryQuestion);

        $repositoryQuestion = new Question(sprintf('Where can users report issues?  <comment>[%s]</comment>', $composerInfo?->getIssues()), $composerInfo?->getIssues());
        $repositoryQuestion->setAutocompleterValues([
            'https://github.com/' . $composerInfo?->getComposerName() . '/issues',
            'https://gitlab.com/' . $composerInfo?->getComposerName() . '/-/issues',
            $composerInfo?->getHomepage(),
        ]);

        $issuesUrl = $helper->ask($input, $output, $repositoryQuestion);
        $typo3CoreVersion = $helper->ask($input, $output, new Question('Which version of TYPO3 is the prefered version to use?  <comment>[stable]</comment>: ', 'stable'));

        $output->writeln('Thank you for your input. We will setup your "Documentation" folder now.');

        // Create the project structure
        if (!@mkdir('Documentation') && !is_dir('Documentation')) {
            $output->writeln('<error>Directory "Documentation" was not created</error>');
            return Command::FAILURE;
        }

        assert(is_string($projectName));
        assert(is_string($projectHomePage));
        assert(is_string($repositoryUrl));
        assert(is_string($issuesUrl));
        assert(is_string($typo3CoreVersion));


        file_put_contents(
            'Documentation/guides.xml',
            <<<XML
                <?xml version="1.0" encoding="UTF-8" ?>
                <guides
                    xmlns="https://www.phpdoc.org/guides"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                    xsi:schemaLocation="https://www.phpdoc.org/guides vendor/phpdocumentor/guides-cli/resources/schema/guides.xsd"
                    input-format="rst"
                >
                    <project
                        title="$projectName"
                        copyright="The contributors"
                    />
                    <extension
                        class="\T3Docs\Typo3DocsTheme\DependencyInjection\Typo3DocsThemeExtension"
                        edit-on-github="{$composerInfo?->getComposerName()}"
                        edit-on-github-branch="main"
                        interlink-shortcode="{$composerInfo?->getComposerName()}"
                        project-home="$projectHomePage"
                        project-issues="$issuesUrl"
                        project-repository="$repositoryUrl"
                        typo3-core-preferred="$typo3CoreVersion"
                    />
                </guides>
                XML
        );

        return Command::SUCCESS;
    }


    private function getComposerInfo(OutputInterface $output): ComposerPackage|null
    {
        if (!file_exists('composer.json')) {
            $output->writeln('No <comment>composer.json</comment> file was found in the current directory.');
            return null;
        }

        $output->writeln('A <comment>composer.json</comment> file was found in the current directory.');
        $packageName = $this->fetchComposerPackageName();
        if (!is_string($packageName)) {
            $output->writeln('The package name could not be determined from the <comment>composer.json</comment> file.');
            return null;
        }

        $composerInfo = (new PackagistService())->getComposerInfo($packageName);
        $output->writeln('The package <comment>' . $composerInfo->getComposerName() . '</comment> was found on packagist.org');

        return $composerInfo;
    }

    private function fetchComposerPackageName(): string|null
    {
        $fileContent = file_get_contents('composer.json');
        if ($fileContent === false) {
            return null;
        }

        $composerJson = json_decode($fileContent, true);

        if (!is_array($composerJson)) {
            return null;
        }

        return $composerJson['name'];
    }

}
