<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\EventListeners;

use League\Flysystem\FilesystemException;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use phpDocumentor\Guides\Event\PostRenderProcess;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;

final class CopyResources
{
    private const SOURCE_PATH = '../../resources/public';
    private const DESTINATION_PATH = '/_resources';

    public function __construct(
        private readonly LoggerInterface $logger,
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
            $stream = null;
            try {
                $stream = $source->readStream($file->getRelativePathname());
                $destinationPath = sprintf(
                    '%s/%s%s',
                    self::DESTINATION_PATH,
                    $file->getRelativePath() !== '' ? $file->getRelativePath() . '/' : '',
                    $file->getFilename()
                );
                $destination->putStream($destinationPath, $stream);
            } catch (FilesystemException $e) {
                $this->logger->warning(sprintf('Cannot copy resource "%s": %s', $file->getRealPath(), $e->getMessage()));
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }
    }
}
