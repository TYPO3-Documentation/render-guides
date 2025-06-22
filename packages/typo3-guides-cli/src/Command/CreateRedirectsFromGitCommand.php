<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputOption;
use T3Docs\GuidesCli\Git\GitChangeDetector;
use T3Docs\GuidesCli\Redirect\RedirectCreator;

final class CreateRedirectsFromGitCommand extends Command
{
    protected static $defaultName = 'create-redirects-from-git';

    private GitChangeDetector $gitChangeDetector;
    private RedirectCreator $redirectCreator;

    public function __construct(
        ?GitChangeDetector $gitChangeDetector = null,
        ?RedirectCreator $redirectCreator = null
    ) {
        parent::__construct();
        $this->gitChangeDetector = $gitChangeDetector ?? new GitChangeDetector();
        $this->redirectCreator = $redirectCreator ?? new RedirectCreator();
    }

    protected function configure(): void
    {
        $this->setDescription('Creates nginx redirects for files moved in a GitHub pull request.');
        $this->setHelp(
            <<<'EOT'
                The <info>%command.name%</info> command analyzes git history to detect moved files
                in the current branch/PR and creates appropriate nginx redirects for them.

                <info>$ php %command.name% [options]</info>

                EOT
        );

        $this->addOption(
            'base-branch',
            'b',
            InputOption::VALUE_REQUIRED,
            'The base branch to compare changes against (default: main)',
            'main'
        );

        $this->addOption(
            'docs-path',
            'd',
            InputOption::VALUE_REQUIRED,
            'Path to the Documentation directory',
            'Documentation'
        );

        $this->addOption(
            'output-file',
            'o',
            InputOption::VALUE_REQUIRED,
            'Path to the nginx redirect configuration output file',
            'redirects.nginx.conf'
        );
        $this->addOption(
            'versions',
            'r',
            InputOption::VALUE_REQUIRED,
            'Regex of versions to include',
            '(main|13.4|12.4)'
        );
        $this->addOption(
            'path',
            'p',
            InputOption::VALUE_REQUIRED,
            'Path, for example /m/typo3/reference-coreapi/',
            '/'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $baseBranch = $input->getOption('base-branch');
        $docsPath = $input->getOption('docs-path');
        $outputFile = $input->getOption('output-file');
        $versions = $input->getOption('versions');
        $path = $input->getOption('path');

        if (!is_string($baseBranch)) {
            $io->error('Base branch must be a string.');
            return Command::FAILURE;
        }

        if (!is_string($docsPath)) {
            $io->error('Documentation path must be a string.');
            return Command::FAILURE;
        }

        if (!is_string($outputFile)) {
            $io->error('Output file must be a string.');
            return Command::FAILURE;
        }

        if (!is_string($versions) || preg_match($versions, '') === false) {
            $io->error('Versions must be valid regex.');
            return Command::FAILURE;
        }

        if (!is_string($path)) {
            $io->error('Path must be a string.');
            return Command::FAILURE;
        }

        $io->title('Creating nginx redirects from git history');
        $io->text("Base branch: {$baseBranch}");
        $io->text("Documentation path: {$docsPath}");
        $io->text("Output file: {$outputFile}");
        $io->text("Versions regex: {$versions}");
        $io->text("Path: {$path}");

        try {
            $movedFiles = $this->gitChangeDetector->detectMovedFiles($baseBranch, $docsPath);

            if (empty($movedFiles)) {
                $io->success('No moved files detected in this PR.');
                return Command::SUCCESS;
            }

            $io->section('Detected moved files:');
            foreach ($movedFiles as $oldPath => $newPath) {
                $io->text("- <info>{$oldPath}</info> → <info>{$newPath}</info>");
            }

            $this->redirectCreator->setNginxRedirectFile($outputFile);


            $createdRedirects = $this->redirectCreator->createRedirects($movedFiles, $docsPath, $versions, $path);

            $io->section('Created nginx redirects:');
            foreach ($createdRedirects as $source => $target) {
                $sourceUrl = str_replace($docsPath . '/', '', $source);
                $sourceUrl = preg_replace('/\.(rst|md)$/', '', $sourceUrl);

                $targetUrl = str_replace($docsPath . '/', '', $target);
                $targetUrl = preg_replace('/\.(rst|md)$/', '', $targetUrl);
                if (is_string($targetUrl) === false) {
                    $io->error('Target construct failed');
                    return Command::FAILURE;
                }

                $io->text("- <info>/{$sourceUrl}</info> → <info>/{$targetUrl}</info>");
            }

            $io->success(sprintf('Nginx redirects created successfully in %s!', $outputFile));
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
