<?php

namespace T3Docs\Typo3DocsTheme\Nodes;

use phpDocumentor\Guides\Nodes\Inline\PlainTextInlineNode;
use phpDocumentor\Guides\Nodes\InlineCompoundNode;
use phpDocumentor\Guides\Nodes\LinkTargetNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\Nodes\OptionalLinkTargetsNode;
use phpDocumentor\Guides\Nodes\PrefixedLinkTargetNode;
use phpDocumentor\Guides\RestructuredText\Nodes\GeneralDirectiveNode;

final class ViewHelperNode extends GeneralDirectiveNode implements LinkTargetNode, OptionalLinkTargetsNode, PrefixedLinkTargetNode
{
    use CompiledPropertiesTrait {
        removeNode as private removeCompiledNode;
    }

    private const DESCRIPTION = 'description';
    private const SECTION = 'section';
    private const EXAMPLE = 'example';

    /** The ":display:" value that shows each part. */
    private const DISPLAYED_AS = [
        self::DESCRIPTION => 'description',
        self::SECTION => 'sections',
        self::EXAMPLE => 'examples',
    ];

    /**
     * What each node of the documentation is shown as, by its position: the
     * description, a section or an example. The documentation is compiled as
     * children after the arguments, and the compiler replaces its nodes with
     * what it makes of them -- since phpDocumentor/guides 1.11 a "note" in it
     * becomes an admonition only then. So the parts are taken from the
     * documentation when they are asked for, not kept as nodes of their own
     * that would stay as parsed.
     *
     * @var list<string>
     */
    private array $roles = [];

    public const LINK_TYPE = 'typo3:viewhelper';
    public const LINK_PREFIX = 'viewhelper-';
    /**
     * @param Node[] $documentation
     * @param Node[] $description
     * @param Node[] $sections
     * @param Node[] $examples
     * @param array<string, string> $docTags
     * @param string[] $display
     * @param array<string, ViewHelperArgumentNode> $arguments
     */
    public function __construct(
        private readonly string $id,
        private readonly string $tagName,
        private readonly string $shortClassName,
        private readonly string $namespace,
        private readonly string $className,
        private array $documentation,
        array $description,
        array $sections,
        array $examples,
        private readonly string $xmlNamespace,
        private readonly bool $allowsArbitraryArguments,
        private readonly array $docTags,
        private readonly string $gitHubLink = '',
        private readonly bool $noindex = false,
        private readonly array $display = [],
        private array $arguments = [],
        private readonly string $namespaceAlias = '',
        private readonly string $rawDocumentation = '',
    ) {
        $this->documentation = array_values($documentation);
        parent::__construct('viewhelper', $tagName, new InlineCompoundNode([new PlainTextInlineNode($tagName)]), $this->documentation);
        foreach ($this->documentation as $node) {
            $this->roles[] = match (true) {
                in_array($node, $description, true) => self::DESCRIPTION,
                in_array($node, $sections, true) => self::SECTION,
                in_array($node, $examples, true) => self::EXAMPLE,
                default => '',
            };
        }
    }

    /**
     * @return Node[]
     */
    public function getSections(): array
    {
        return $this->part(self::SECTION);
    }

    /**
     * @return Node[]
     */
    public function getExamples(): array
    {
        return $this->part(self::EXAMPLE);
    }

    /**
     * @return string[]
     */
    public function getDisplay(): array
    {
        return $this->display;
    }

    /**
     * @return Node[]
     */
    public function getDescription(): array
    {
        return $this->part(self::DESCRIPTION);
    }

    /**
     * The prefix a template uses for the ViewHelper's namespace, such as "f"
     * or "be": the "namespaceAlias" of the file that describes it.
     */
    public function getNamespaceAlias(): string
    {
        return $this->namespaceAlias;
    }

    /** The name a template writes, such as "f:format.html", or the bare tag name without a prefix. */
    public function getFullName(): string
    {
        return $this->namespaceAlias === '' ? $this->tagName : $this->namespaceAlias . ':' . $this->tagName;
    }

    /** The documentation as the describing file writes it, before it is parsed. */
    public function getRawDocumentation(): string
    {
        return $this->rawDocumentation;
    }

    public function getTagName(): string
    {
        return $this->tagName;
    }

    public function getShortClassName(): string
    {
        return $this->shortClassName;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @return Node[]
     */
    public function getDocumentation(): array
    {
        return $this->documentation;
    }

    public function getXmlNamespace(): string
    {
        return $this->xmlNamespace;
    }

    public function isAllowsArbitraryArguments(): bool
    {
        return $this->allowsArbitraryArguments;
    }

    /**
     * @return string[]
     */
    public function getDocTags(): array
    {
        return $this->docTags;
    }

    /**
     * @return ViewHelperArgumentNode[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @param array<string, ViewHelperArgumentNode> $arguments
     */
    public function setArguments(array $arguments): void
    {
        $this->arguments = $arguments;
    }

    public function getLinkType(): string
    {
        return self::LINK_TYPE;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLinkText(): string
    {
        return $this->tagName;
    }

    public function getAnchor(): string
    {
        return self::LINK_PREFIX . $this->id;
    }

    public function isNoindex(): bool
    {
        return $this->noindex;
    }

    public function getPrefix(): string
    {
        return self::LINK_PREFIX;
    }

    public function getGitHubLink(): string
    {
        return $this->gitHubLink;
    }

    /** @return list<string> */
    protected function compiledProperties(): array
    {
        return ['documentation'];
    }

    /**
     * Only the documentation the ViewHelper shows: with ":display: description"
     * its sections are not on the page, and must not become sections of it.
     */
    protected function compilesItem(string $property, int|string $key): bool
    {
        if (in_array('documentation', $this->display, true)) {
            return true;
        }
        $role = $this->roles[$key] ?? '';

        return $role !== '' && in_array(self::DISPLAYED_AS[$role], $this->display, true);
    }

    public function removeNode(int $key): static
    {
        // The index in the documentation, which is not the position among the
        // children when a part is not shown.
        $index = $this->propertySlot($key)[1] ?? null;
        $result = $this->removeCompiledNode($key);
        if (is_int($index)) {
            array_splice($result->roles, $index, 1);
        }

        return $result;
    }

    /** @return Node[] the nodes of the documentation shown as this part */
    private function part(string $role): array
    {
        $part = [];
        foreach ($this->documentation as $position => $node) {
            if (($this->roles[$position] ?? '') === $role) {
                $part[] = $node;
            }
        }

        return $part;
    }
}
