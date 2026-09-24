<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Renderer;

use phpDocumentor\Guides\Handlers\RenderCommand;
use phpDocumentor\Guides\ReferenceResolvers\AnchorNormalizer;
use phpDocumentor\Guides\ReferenceResolvers\DocumentNameResolverInterface;
use phpDocumentor\Guides\RenderContext;
use phpDocumentor\Guides\Renderer\TypeRenderer;
use phpDocumentor\Guides\Renderer\UrlGenerator\UrlGeneratorInterface;
use T3Docs\Typo3DocsTheme\Nodes\Typo3FileNode;
use T3Docs\Typo3DocsTheme\Permalinks\Permalinks;
use T3Docs\Typo3DocsTheme\ReferenceResolvers\ObjectsInventory\ExternalFileObjects;
use T3Docs\Typo3DocsTheme\ReferenceResolvers\ObjectsInventory\FileObject;
use T3Docs\Typo3DocsTheme\ReferenceResolvers\ObjectsInventory\ObjectInventory;

use function json_encode;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * The files a manual defines with ".. typo3:file::", as one JSON file.
 *
 * "files.json" at the root of the manual: for each file what the popup of a
 * ":file:" shows about it, the regex a ":file:" is matched with, and the page
 * and anchor that define it, relative to this file. That is what lets another
 * manual link a file it names to its definition. @see ExternalFileObjects
 *
 * "objects.inv.json" has the address but not the regex, and a ":file:" rarely
 * names a file by its id: it names a path, and the regex decides which file
 * the path is.
 *
 * Written only by a manual that defines files.
 */
final class FilesJsonRenderer implements TypeRenderer
{
    public function __construct(
        private readonly ObjectInventory $objectInventory,
        private readonly AnchorNormalizer $anchorNormalizer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly DocumentNameResolverInterface $documentNameResolver,
        private readonly Permalinks $permalinks,
    ) {}

    public function render(RenderCommand $renderCommand): void
    {
        $projectNode = $renderCommand->getProjectNode();
        $context = RenderContext::forProject(
            $projectNode,
            $renderCommand->getDocumentArray(),
            $renderCommand->getOrigin(),
            $renderCommand->getDestination(),
            $renderCommand->getDestinationPath(),
            'html',
        )->withOutputFilePath(ExternalFileObjects::FILE_NAME);

        $files = [];
        foreach ($this->objectInventory->getGroup(FileObject::KEY) as $fileObject) {
            if (!$fileObject instanceof FileObject) {
                continue;
            }
            $target = $projectNode->getInternalTarget(
                $this->anchorNormalizer->reduceAnchor($fileObject->id),
                Typo3FileNode::LINK_TYPE,
            );
            if ($target === null) {
                continue;
            }
            $files[] = [
                ...$fileObject->toArray(),
                'path' => $this->documentNameResolver->canonicalUrl(
                    '',
                    $this->urlGenerator->createFileUrl($context, $target->getDocumentPath(), $target->getPrefix() . $target->getAnchor()),
                ),
            ];
        }

        if ($files === []) {
            return;
        }

        $renderCommand->getDestination()->put(
            ExternalFileObjects::FILE_NAME,
            (string) json_encode(
                [
                    'project' => [
                        'title' => $projectNode->getTitle() ?? '',
                        'version' => $this->permalinks->normalizeVersion($projectNode->getVersion()),
                    ],
                    'files' => $files,
                ],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            ),
        );
    }
}
