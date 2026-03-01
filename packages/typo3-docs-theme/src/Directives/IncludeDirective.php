<?php

declare(strict_types=1);

/**
 * This file is part of phpDocumentor.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link https://phpdoc.org
 */

namespace T3Docs\Typo3DocsTheme\Directives;

use Flyfinder\Specification\Glob;
use phpDocumentor\FileSystem\FileSystem;
use phpDocumentor\Guides\Files;
use phpDocumentor\Guides\Nodes\CodeNode;
use phpDocumentor\Guides\Nodes\CollectionNode;
use phpDocumentor\Guides\Nodes\LiteralBlockNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\RestructuredText\Directives\BaseDirective;
use phpDocumentor\Guides\RestructuredText\Parser\BlockContext;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;
use phpDocumentor\Guides\RestructuredText\Parser\Productions\DocumentRule;
use RuntimeException;
use T3Docs\Typo3DocsTheme\Nodes\EditOnGithubIncludeNode;

use function array_key_exists;
use function explode;
use function sprintf;
use function str_replace;

final class IncludeDirective extends BaseDirective
{
    public function __construct(private readonly DocumentRule $startingRule) {}

    public function getName(): string
    {
        return 'include';
    }

    /** {@inheritDoc} */
    public function processNode(
        BlockContext $blockContext,
        Directive    $directive,
    ): Node {
        $inputPath = $directive->getData();
        if (str_contains($inputPath, '*')) {
            return $this->resolveGlobInclude($blockContext, $inputPath, $directive);
        }
        return $this->resolveBasicInclude($blockContext, $inputPath, $directive);
    }


    /**
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function resolveGlobInclude(BlockContext $blockContext, string $inputPath, Directive $directive): LiteralBlockNode|CollectionNode|CodeNode
    {
        $parserContext = $blockContext->getDocumentParserContext()->getParser()->getParserContext();
        $path = $parserContext->absoluteRelativePath($inputPath);
        $path = $this->normalizeGlob($path);

        /** @var FileSystem $origin */
        $origin = $parserContext->getOrigin();

        $matches = $origin->find(new Glob('/' . ltrim($path, '/')));
        $files = new Files();
        foreach ($matches as $file) {
            if (is_string($file['path'])) {
                $files->add($file['path']);
            }
        }

        if ($files->count() === 0) {
            throw new RuntimeException(
                sprintf('No files matched the glob pattern "%s".', $path),
            );
        }

        $nodes = [];

        // Loop through each matched file
        foreach ($files as $file) {
            if (!$origin->has($file)) {
                throw new RuntimeException(
                    sprintf('Include "%s" (%s) does not exist or is not readable.', $directive->getData(), $file),
                );
            }

            // Get the collection of nodes from each path
            $nodes[] = $this->getCollectionFromPath($origin, $file, $directive, $blockContext);
        }

        // If only one node is found, return it directly
        if (count($nodes) === 1) {
            return $nodes[0];
        }

        // Otherwise, return a CollectionNode of all nodes
        return new CollectionNode($nodes);
    }

    /**
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function resolveBasicInclude(BlockContext $blockContext, string $inputPath, Directive $directive): LiteralBlockNode|CollectionNode|CodeNode
    {
        $parserContext = $blockContext->getDocumentParserContext()->getParser()->getParserContext();
        $path = $parserContext->absoluteRelativePath($inputPath);

        $origin = $parserContext->getOrigin();
        if (!$origin->has($path)) {
            throw new RuntimeException(
                sprintf('Include "%s" (%s) does not exist or is not readable.', $directive->getData(), $path),
            );
        }

        return $this->getCollectionFromPath($origin, $path, $directive, $blockContext);
    }

    /**
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function getCollectionFromPath(\League\Flysystem\FilesystemInterface|\phpDocumentor\FileSystem\FileSystem $origin, string $path, Directive $directive, BlockContext $blockContext): LiteralBlockNode|CollectionNode|CodeNode
    {
        $contents = $origin->read($path);

        if ($contents === false) {
            throw new RuntimeException(sprintf('Could not load file from path %s', $path));
        }

        if (array_key_exists('literal', $directive->getOptions())) {
            $contents = str_replace("\r\n", "\n", $contents);

            return new LiteralBlockNode($contents);
        }

        if (array_key_exists('code', $directive->getOptions())) {
            $contents = str_replace("\r\n", "\n", $contents);
            $codeNode = new CodeNode(
                explode('\n', $contents),
            );
            $codeNode->setLanguage((string)$directive->getOption('code')->getValue());

            return $codeNode;
        }

        $currentDocument = $blockContext->getDocumentParserContext()->getDocument();
        $subContext = new BlockContext($blockContext->getDocumentParserContext(), $contents);
        $document = $this->startingRule->apply($subContext);

        //Reset the document, as it was changed by the apply method.
        $blockContext->getDocumentParserContext()->setDocument($currentDocument);
        $path = '/' . trim($path, '/');
        $buttons = [];
        if ($directive->getOptionBool('show-buttons')) {
            $buttons[] = new EditOnGithubIncludeNode($path);
        }
        return new CollectionNode(array_values(array_merge($buttons, $document->getChildren())));
    }

    /**
     * Normalizes glob pattern to a flat file path.
     *
     * A glob shall not contain `..` or `.` this will break the
     * matching. Therefor we need to remove all those constructions from
     * the input.
     */
    private function normalizeGlob(string $path): string
    {
        $parts = explode('/', $path);
        $newParts = [];
        foreach ($parts as $part) {
            if ($part === '.') {
                continue;
            }

            if ($part === '..') {
                array_pop($newParts);
                continue;
            }

            $newParts[] = $part;
        }

        return implode('/', $newParts);
    }
}
