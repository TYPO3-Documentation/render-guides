<?php

declare(strict_types=1);


namespace T3Docs\Typo3DocsTheme\EventListeners;


use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use League\Flysystem\Filesystem;
use phpDocumentor\Guides\Event\PostRenderProcess;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;

final class CopyStaticAssets
{
    private const SOURCE_ASSETS_PATH = '../../_assets';
    private const DESTINATION_ASSETS_PATH = '/_assets';

    public function __construct(
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function __invoke(PostRenderProcess $event): void
    {
        if ($event->getCommand()->getOutputFormat() !== 'html') {
            return;
        }

        $fullAssetsPath = realpath(__DIR__ . '/' . self::SOURCE_ASSETS_PATH);
        if ($fullAssetsPath === false) {
            $this->logger->warning('assets path is not available');
            return;
        }

        $source = new Filesystem(new Local($fullAssetsPath));
        $destination = $event->getCommand()->getDestination();

        $finder = new Finder();
        $finder->files()->in($fullAssetsPath);

        foreach ($finder as $file) {
            $stream = $source->readStream($file->getFilename());
            $destination->putStream(self::DESTINATION_ASSETS_PATH . '/' . $file->getFilename(), $stream);
            is_resource($stream) && fclose($stream);
        }
    }
}
