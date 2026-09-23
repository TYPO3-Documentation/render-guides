<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\EventListeners;

use League\Flysystem\FilesystemException;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use phpDocumentor\Guides\Event\PostRenderProcess;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;
use T3Docs\Typo3DocsTheme\Deployment\DeploymentMode;

final class CopyResources
{
    private const SOURCE_PATH = '../../resources/public';
    private const DESTINATION_PATH = '/_resources';

    /**
     * The proxies fetch what only docs.typo3.org may fetch of itself: the menu
     * of all documentation, and the versions of a manual. A page rendered
     * anywhere else is refused by the browser as a foreign origin, so it asks
     * its own server instead, which passes the request on. They are PHP, so
     * they do nothing on a server that only serves files, and they have no
     * place in a deployed render, which needs no proxy.
     */
    private const PROXY_SOURCE_PATH = '../../assets/js';
    private const PROXY_FILE_NAMES = ['menu-proxy.php', 'versions-proxy.php'];

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly DeploymentMode $deploymentMode,
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

        $this->copyProxies($event);
    }

    private function copyProxies(PostRenderProcess $event): void
    {
        if ($this->deploymentMode->isForDeployment()) {
            return;
        }

        $path = realpath(__DIR__ . '/' . self::PROXY_SOURCE_PATH);
        if ($path === false) {
            return;
        }

        $source = new Filesystem(new LocalFilesystemAdapter($path));
        $destination = $event->getCommand()->getDestination();
        foreach (self::PROXY_FILE_NAMES as $filename) {
            $stream = null;
            try {
                $stream = $source->readStream($filename);
                $destination->putStream(self::DESTINATION_PATH . '/js/' . $filename, $stream);
            } catch (FilesystemException $e) {
                $this->logger->warning(sprintf('Cannot copy proxy "%s": %s', $filename, $e->getMessage()));
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }
    }
}
