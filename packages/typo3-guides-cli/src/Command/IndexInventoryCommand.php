<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Command;

use InvalidArgumentException;
use PDOException;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use T3Docs\GuidesCli\InventoryIndex\Artifact;
use T3Docs\GuidesCli\InventoryIndex\InventoryDatabase;
use T3Docs\GuidesCli\InventoryIndex\InventoryIndexer;

use function sprintf;
use function trim;

/**
 * Writes one rendered manual's objects.inv.json into the SQLite index that
 * resolves permalinks and version switches across all manuals and versions.
 */
final class IndexInventoryCommand extends Command
{
    protected static $defaultName = 'index-inventory';

    protected function configure(): void
    {
        $this->setDescription('Index a rendered manual\'s objects.inv.json into the SQLite database.');
        $this->setHelp(
            <<<'EOT'
                The <info>%command.name%</info> command reads the <info>objects.inv.json</info>
                of one rendered manual and writes its repository and permalinks into the
                central SQLite index. Indexing the same repository, version and language
                again replaces what was indexed before.

                <info>$ php %command.name% Documentation-GENERATED-temp/objects.inv.json \
                --database=docs.sqlite \
                --repository=t3docs/render-guides \
                --url=https://docs.typo3.org/other/t3docs/render-guides/main/en-us/ \
                --version=main \
                --language=en-us</info>

                EOT
        );

        $this->setDefinition([
            new InputArgument(
                'inventory',
                InputArgument::REQUIRED,
                'Path to the rendered objects.inv.json file.',
            ),
            new InputOption(
                'database',
                null,
                InputOption::VALUE_REQUIRED,
                'Path to the SQLite database. Created when it does not exist.',
            ),
            new InputOption(
                'repository',
                null,
                InputOption::VALUE_REQUIRED,
                'The manual\'s repository, for example "t3docs/render-guides" or "georgringer/news".',
            ),
            new InputOption(
                'url',
                null,
                InputOption::VALUE_REQUIRED,
                'The address the manual is deployed at.',
            ),
            new InputOption(
                'version',
                null,
                InputOption::VALUE_REQUIRED,
                'The deployed version, for example "main" or "13.4".',
            ),
            new InputOption(
                'language',
                null,
                InputOption::VALUE_REQUIRED,
                'The deployed language, for example "en-us".',
            ),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $inventory = $this->requiredString($input, 'inventory', true, $io);
        $database = $this->requiredString($input, 'database', false, $io);
        $repository = $this->requiredString($input, 'repository', false, $io);
        $url = $this->requiredString($input, 'url', false, $io);
        $version = $this->requiredString($input, 'version', false, $io);
        $language = $this->requiredString($input, 'language', false, $io);

        if ($inventory === null || $database === null || $repository === null || $url === null || $version === null || $language === null) {
            return Command::FAILURE;
        }

        try {
            $artifact = new Artifact($repository, $url, $version, $language);
            $indexer = new InventoryIndexer(InventoryDatabase::open($database));
            $count = $indexer->indexFile($artifact, $inventory);
        } catch (InvalidArgumentException|RuntimeException|PDOException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf(
            'Indexed %d permalinks of %s %s (%s) into %s.',
            $count,
            $artifact->repository,
            $artifact->version,
            $artifact->language,
            $database,
        ));

        return Command::SUCCESS;
    }

    private function requiredString(InputInterface $input, string $name, bool $isArgument, SymfonyStyle $io): ?string
    {
        $value = $isArgument ? $input->getArgument($name) : $input->getOption($name);
        if (!is_string($value) || trim($value) === '') {
            $io->error(sprintf('The "%s" %s is required.', $name, $isArgument ? 'argument' : 'option'));

            return null;
        }

        return $value;
    }
}
