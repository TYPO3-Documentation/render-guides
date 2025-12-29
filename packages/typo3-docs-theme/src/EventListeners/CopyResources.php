<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\EventListeners;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use phpDocumentor\Guides\Event\PostRenderProcess;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;

final readonly class CopyResources
{
    private const string SOURCE_PATH = '../../resources/public';
    private const string DESTINATION_PATH = '/_resources';

    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function __invoke(PostRenderProcess $event): void
    {
        if ($event->getCommand()->getOutputFormat() !== 'html') {
            return;
        }

        $path = __DIR__ . '/' . self::SOURCE_PATH;
        $fullResourcesPath = realpath($path);
        if ($fullResourcesPath === false) {
            $this->logger->warning(sprintf(
                'Resources path "%s" is not available!',
                $path,
            ));
            return;
        }

        $source = new Filesystem(new LocalFilesystemAdapter($fullResourcesPath));

        $destination = $event->getCommand()->getDestination();

        $finder = new Finder();
        $finder->files()->in($fullResourcesPath);

        foreach ($finder as $file) {
            $stream = $source->readStream($file->getRelativePathname());
            if ($stream === false) {
                $this->logger->warning(sprintf('Cannot read stream from "%s"', $file->getRealPath()));
                continue;
            }

            $destinationPath = sprintf(
                '%s/%s%s',
                self::DESTINATION_PATH,
                $file->getRelativePath() !== '' ? $file->getRelativePath() . '/' : '',
                $file->getFilename()
            );
            $destination->putStream($destinationPath, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }
}
