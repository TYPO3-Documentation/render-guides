<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use T3Docs\GuidesCli\Generation\DocumentationGenerator;
use T3Docs\VersionHandling\Packagist\ComposerPackage;
use T3Docs\VersionHandling\Packagist\PackagistService;
use T3Docs\VersionHandling\Typo3VersionMapping;

/**
 * You can run this command, for example like
 *
 * ddev exec packages/typo3-guides-cli/bin/typo3-guides init --working-dir=packages/my-extension
 *
 */
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
                create a new documentation project in the working directory
                (default: The directory from which you run this command).

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
            $workdir = (string) $workdir;

            if (chdir($workdir)) {
                $output->writeln('<info>Changed working directory to ' . getcwd() . '</info>');
            } else {
                $output->writeln('<error>Could not change working directory to ' . $workdir . '</error>');
                return Command::INVALID;
            }
        }

        if ($input->getOption('quiet')) {
            echo '<error>This command is interactive and requires user input. </error>' . PHP_EOL;
            return Command::INVALID;
        }

        if (is_file('Documentation/guides.xml')) {
            $output->writeln('<error>A file "Documentation/guides.xml" already exists in this directory</error>');
            return Command::INVALID;
        }

        $output->writeln('Welcome to the <comment>TYPO3 documentation guide</comment> setup wizard');
        $output->writeln('This wizard will help you to create a new documentation project in the current directory (or work directory).');
        $output->writeln('');

        $composerInfo = $this->getComposerInfo($output);

        if ($composerInfo === null) {
            $output->writeln('<error>No composer.json was found in the current or work directory. Use option --working-dir to set the work directory.</error>');
            return Command::FAILURE;
        }

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $question = new Question(sprintf('Do you want to use reStructuredText(rst) or MarkDown(md)? <comment>[rst, md]</comment>: '), 'rst');
        $question->setValidator(function ($answer) {
            if (is_null($answer) || !in_array($answer, [
                    'rst',
                    'md',
                ], true)) {
                throw new \RuntimeException('Choose reStructuredText(rst) or MarkDown(md). ');
            }
            return $answer;
        });
        $format = $helper->ask($input, $output, $question);

        $projectNameQuestion = new Question(
            sprintf('What is the title of your documentation? <comment>[%s]</comment>: ', $composerInfo->getComposerName()),
            $composerInfo->getComposerName()
        );
        $projectNameQuestion->setValidator(function ($answer) {
            if (is_null($answer) || trim($answer) === '') {
                throw new \RuntimeException('The project title cannot be empty.');
            }
            return $answer;
        });

        $projectName = $helper->ask($input, $output, $projectNameQuestion);

        $question = $this->createValidatedUrlQuestion(
            'What is the URL of your project\'s homepage? <comment>[%s]</comment>: ',
            [
                $composerInfo->getHomepage(),
                'https://extensions.typo3.org/package/' . $composerInfo->getExtensionKey(),
            ]
        );
        $projectHomePage = $helper->ask($input, $output, $question);


        $question = $this->createValidatedUrlQuestion(
            'What is the URL of your project\'s repository?   <comment>[%s]</comment>',
            [
                $composerInfo->getHomepage(),
                'https://github.com/' . $composerInfo->getComposerName(),
                'https://gitlab.com/' . $composerInfo->getComposerName(),
            ]
        );
        $repositoryUrl = $helper->ask($input, $output, $question);

        $question = $this->createValidatedUrlQuestion(
            'Where can users report issues?  <comment>[%s]</comment>',
            [
                $composerInfo->getIssues(),
                $repositoryUrl . '/issues',
                $repositoryUrl . '/-/issues',
            ]
        );

        $issuesUrl = $helper->ask($input, $output, $question);

        // Get LTS versions from the Typo3VersionMapping class
        $ltsEnumCases = Typo3VersionMapping::getLtsVersions();

        // Extract the values of these enum cases
        $ltsValues = array_map(static fn(Typo3VersionMapping $enumValue) => $enumValue->value, $ltsEnumCases);

        $question = new Question('Which version of TYPO3 is the preferred version to use?  <comment>[stable]</comment>: ', Typo3VersionMapping::getDefault()->value);
        $question->setAutocompleterValues([Typo3VersionMapping::Stable->value, Typo3VersionMapping::Dev->value, ...$ltsValues]);
        $typo3CoreVersion = $helper->ask($input, $output, $question);

        $question = new Question('Do you want generate some Documentation? (yes/no) ', 'yes');
        $question->setValidator(function ($answer) {
            if (!in_array(strtolower($answer), [
                'yes',
                'y',
                'no',
                'n',
            ], true)) {
                throw new \RuntimeException('Please answer with yes, no, y, or n.');
            }
            return strtolower($answer);
        });

        $answer = $helper->ask($input, $output, $question);
        $enableExampleFileGeneration = in_array($answer, [
            'yes',
            'y',
        ], true);

        $question = new Question('Does your extension offer a site set to be included? If so enter the name: ');
        $siteSet = $helper->ask($input, $output, $question);
        $siteSetPath = '';
        $siteSetDefinition = '';
        if (is_string($siteSet) && $siteSet !== '') {
            $question = new Question('Enter the path to your site set: ');
            $siteSetPath = $helper->ask($input, $output, $question);
            if (is_file($siteSetPath . '/settings.definitions.yaml')) {
                $siteSetDefinition = $siteSetPath . '/settings.definitions.yaml';
            }
        }

        $output->writeln('Thank you for your input. We will setup your "Documentation" folder now.');

        // Create the project structure
        if (!@mkdir('Documentation') && !is_dir('Documentation')) {
            $output->writeln('<error>Directory "Documentation" was not created</error>');
            return Command::FAILURE;
        }


        $outputDir = 'Documentation';
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0o777, true);
        }

        assert(is_string($repositoryUrl ?? ''));
        $editOnGitHub = null;
        if (str_starts_with($repositoryUrl ?? '', 'https://github.com/')) {
            $editOnGitHub = str_replace('https://github.com/', '', $repositoryUrl);
        }
        // Define your data
        $data = [
            'format' => $format,
            'useMd' => ($format === 'md'),
            'projectName' => $projectName,
            'description' => $composerInfo->getDescription(),
            'composerName' => $composerInfo->getComposerName(),
            'projectHomePage' => $projectHomePage,
            'issuesUrl' => $issuesUrl,
            'repositoryUrl' => $repositoryUrl,
            'typo3CoreVersion' => $typo3CoreVersion,
            'editOnGitHub' => $editOnGitHub,
            'siteSet' => $siteSet,
            'siteSetPath' => $siteSetPath,
            'siteSetDefinition' => $siteSetDefinition,
        ];
        (new DocumentationGenerator())->generate($data, __DIR__ . '/../../resources/templates', $outputDir, $enableExampleFileGeneration);

        return Command::SUCCESS;
    }

    /**
     * @param array<mixed> $autocompleteValues
     */
    private function createValidatedUrlQuestion(string $questionText, array $autocompleteValues = []): Question
    {
        $default = null;
        $autocompleteValuesFiltered = [];
        foreach ($autocompleteValues as $value) {
            if ($value !== null && $value !== '' && is_scalar($value)) {
                $default ??= (string)$value;
                $autocompleteValuesFiltered[] = (string)$value;
            }
        }
        $question = new Question(sprintf($questionText, $default), $default);
        if (!empty($autocompleteValues)) {
            $question->setAutocompleterValues($autocompleteValuesFiltered);
        }
        $question->setValidator(function ($answer) {
            if (!is_null($answer) && $answer !== '' && !filter_var($answer, FILTER_VALIDATE_URL)) {
                throw new \RuntimeException('The URL is not valid');
            }
            return $answer;
        });

        return $question;
    }

    private function getComposerInfo(OutputInterface $output): ComposerPackage|null
    {
        if (!is_file('composer.json')) {
            $output->writeln('No <comment>composer.json</comment> file was found in the current directory.');
            return null;
        }

        $output->writeln('A <comment>composer.json</comment> file was found in the current directory.');

        $composerInfo = (new PackagistService())->getComposerInfoFromJson($this->fetchComposerArray() ?? []);

        return $composerInfo;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchComposerArray(): array|null
    {
        $fileContent = file_get_contents('composer.json');
        if ($fileContent === false) {
            return null;
        }

        $composerJson = json_decode($fileContent, true);

        if (!is_array($composerJson)) {
            return null;
        }

        return $composerJson;
    }

}
