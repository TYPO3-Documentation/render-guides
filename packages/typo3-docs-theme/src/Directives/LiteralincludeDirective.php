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

use phpDocumentor\Guides\Nodes\CodeNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\RestructuredText\Directives\BaseDirective;
use phpDocumentor\Guides\RestructuredText\Directives\OptionMapper\CodeNodeOptionMapper;
use phpDocumentor\Guides\RestructuredText\Parser\BlockContext;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;
use Psr\Log\LoggerInterface;
use RuntimeException;

use function explode;
use function sprintf;

final class LiteralincludeDirective extends BaseDirective
{
    public function __construct(
        private readonly CodeNodeOptionMapper $codeNodeOptionMapper,
        private readonly LoggerInterface      $logger,
    ) {}

    public function getName(): string
    {
        return 'literalinclude';
    }

    private function detectLanguageFromExtension(string $path): ?string
    {
        $extensionMap = [
            'html' => 'html',
            'php' => 'php',
            'typoscript' => 'typoscript',
            'tsconfig' => 'typoscript',
            'xml' => 'xml',
            'json' => 'json',
            'yaml' => 'yaml',
            'yml' => 'yaml',
            'js' => 'javascript',
            'css' => 'css',
            'scss' => 'scss',
            'ts' => 'typescript',
            'txt' => 'plaintext',
            'htaccess' => 'plaintext',
            'rst' => 'rest',
            'diff' => 'diff',
        ];

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        if (isset($extensionMap[$extension])) {
            return $extensionMap[$extension];
        }
        return null;
    }

    /** {@inheritDoc} */
    public function processNode(
        BlockContext $blockContext,
        Directive    $directive,
    ): Node {
        $parser = $blockContext->getDocumentParserContext()->getParser();
        $parserContext = $parser->getParserContext();
        $path = $parserContext->absoluteRelativePath($directive->getData());

        $origin = $parserContext->getOrigin();
        if (!$origin->has($path)) {
            throw new RuntimeException(
                sprintf('Include "%s" (%s) does not exist or is not readable.', $directive->getData(), $path),
            );
        }

        $contents = $origin->read($path);

        if ($contents === false) {
            throw new RuntimeException(sprintf('Could not load file from path %s', $path));
        }
        $language = $this->detectLanguageFromExtension($path);
        if ($directive->hasOption('language')) {
            $language = $directive->getOptionString('language');
        }
        if ($language === null) {
            $this->logger->warning(
                sprintf(
                    'Language of `..  literalinclude:: %s` could not be autodetected. '
                    . 'Use property `:language: [langugage]` to explicitly set the language. '
                    . 'Defaulting to `plaintext`. ',
                    $directive->getData()
                ),
                $blockContext->getLoggerInformation()
            );
            $language = 'plaintext';
        }

        $codeNode = new CodeNode(explode("\n", $contents), $language);
        $this->codeNodeOptionMapper->apply($codeNode, $directive->getOptions(), $blockContext);

        $path = '/' . trim($path, '/');
        $codeNode = $codeNode->withKeepExistingOptions([
            'source' => $directive->getData(),
            'path' => $path,
        ]);

        return $codeNode;
    }
}
