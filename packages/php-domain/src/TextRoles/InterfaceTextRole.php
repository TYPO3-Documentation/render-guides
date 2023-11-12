<?php

declare(strict_types=1);

namespace T3Docs\PhpDomain\TextRoles;

use phpDocumentor\Guides\Nodes\Inline\AbstractLinkInlineNode;
use phpDocumentor\Guides\Nodes\Inline\ReferenceNode;
use phpDocumentor\Guides\ReferenceResolvers\AnchorReducer;
use phpDocumentor\Guides\RestructuredText\Parser\DocumentParserContext;
use phpDocumentor\Guides\RestructuredText\Parser\InlineLexer;
use phpDocumentor\Guides\RestructuredText\TextRoles\TextRole;
use Psr\Log\LoggerInterface;

final class InterfaceTextRole implements TextRole
{
    /**
     * @see https://regex101.com/r/OyN05v/1
     */
    private const INTERLINK_NAME_REGEX = '/^([a-zA-Z0-9]+):(.*$)/';

    private readonly InlineLexer $lexer;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AnchorReducer $anchorReducer,
    ) {
        // Do not inject the $lexer. It contains a state.
        $this->lexer = new InlineLexer();
    }
    final public const NAME = 'interface';

    public function getName(): string
    {
        return self::NAME;
    }
    public function getAliases(): array
    {
        return [];
    }

    public function processNode(
        DocumentParserContext $documentParserContext,
        string $role,
        string $content,
        string $rawContent,
    ): AbstractLinkInlineNode {
        $referenceTarget = null;
        $value = null;

        $part = '';
        $this->lexer->setInput($rawContent);
        $this->lexer->moveNext();
        $this->lexer->moveNext();
        while ($this->lexer->token !== null) {
            $token = $this->lexer->token;
            switch ($token->type) {
                case InlineLexer::EMBEDED_URL_START:
                    $value = trim($part);
                    $part = '';

                    break;
                case InlineLexer::EMBEDED_URL_END:
                    if ($value === null) {
                        // not inside the embedded URL
                        $part .= $token->value;
                        break;
                    }

                    if ($this->lexer->peek() !== null) {
                        $this->logger->warning(
                            sprintf(
                                'Reference contains unexpected content after closing `>`: "%s"',
                                $content,
                            ),
                            $documentParserContext->getLoggerInformation(),
                        );
                    }

                    $referenceTarget = $part;
                    $part = '';

                    break 2;
                default:
                    $part .= $token->value;
            }

            $this->lexer->moveNext();
        }

        $value .= trim($part);

        if ($referenceTarget === null) {
            $referenceTarget = $value;
            $value = null;
        }

        return $this->createNode($referenceTarget, $value, $role);
    }

    /** @return ReferenceNode */
    protected function createNode(string $referenceTarget, string|null $referenceName, string $role): AbstractLinkInlineNode
    {
        if (preg_match(self::INTERLINK_NAME_REGEX, $referenceTarget, $matches)) {
            $interlinkDomain = $matches[1];
            $id = $this->anchorReducer->reduceAnchor($matches[2]);
        } else {
            $interlinkDomain = '';
            $id = $this->anchorReducer->reduceAnchor($referenceTarget);
        }

        return new ReferenceNode($id, $referenceName ?? '', $interlinkDomain, 'php:interface');
    }
}
