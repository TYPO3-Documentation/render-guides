<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Tests\Unit\Nodes;

use phpDocumentor\Guides\Nodes\CollectionNode;
use phpDocumentor\Guides\Nodes\CompoundNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\Nodes\ParagraphNode;
use PHPUnit\Framework\TestCase;
use T3Docs\Typo3DocsTheme\Nodes\CompiledPropertiesTrait;

/**
 * The compiler walks a node's children and replaces or removes them by their
 * position. A node with the trait shows the content of its properties as
 * children after its own, so the positions after them are its properties'.
 */
final class CompiledPropertiesTraitTest extends TestCase
{
    public function testThePropertiesComeAfterTheNodesOwnChildren(): void
    {
        [$own, $single, $first, $second] = $this->nodes(4);
        $node = new NodeWithProperties([$own], $single, [$first, $second]);

        self::assertSame([$own, $single, $first, $second], $node->getChildren());
    }

    public function testAReplacedChildIsSetOnTheProperty(): void
    {
        [$own, $single, $first, $second, $replacement] = $this->nodes(5);
        $node = new NodeWithProperties([$own], $single, [$first, $second]);

        $replaced = $node->replaceNode(3, $replacement);

        self::assertSame([$first, $replacement], $replaced->list);
        self::assertSame([$first, $second], $node->list, 'The node itself stays as it was');
        self::assertSame($own, $replaced->replaceNode(0, $own)->getChildren()[0]);
    }

    public function testASinglePropertyKeepsACollection(): void
    {
        [$own, $single, $first, $replacement] = $this->nodes(4);
        $node = new NodeWithProperties([$own], $single, [$first]);

        $replaced = $node->replaceNode(1, $replacement);

        self::assertInstanceOf(CollectionNode::class, $replaced->single);
        self::assertSame([$replacement], $replaced->single->getChildren());
    }

    public function testARemovedChildLeavesTheProperty(): void
    {
        [$own, $single, $first, $second] = $this->nodes(4);
        $node = new NodeWithProperties([$own], $single, [$first, $second]);

        self::assertSame([$second], $node->removeNode(2)->list);
        self::assertNull($node->removeNode(1)->single);
        self::assertSame([], $node->removeNode(0)->getOwnChildren());
    }

    public function testAnEntryThatIsNotCompiledIsNoChild(): void
    {
        [$own, $single, $first, $second, $third, $replacement] = $this->nodes(6);
        $node = new NodeWithProperties([$own], $single, [$first, $second, $third], skip: 1);

        self::assertSame([$own, $single, $first, $third], $node->getChildren());
        // Position 3 is the third entry, not the second
        self::assertSame([$first, $second, $replacement], $node->replaceNode(3, $replacement)->list);
    }

    /** @return list<ParagraphNode> */
    private function nodes(int $count): array
    {
        $nodes = [];
        for ($i = 0; $i < $count; $i++) {
            $nodes[] = new ParagraphNode([]);
        }

        return $nodes;
    }
}

/**
 * @extends CompoundNode<Node>
 * @internal
 */
final class NodeWithProperties extends CompoundNode
{
    use CompiledPropertiesTrait;

    /**
     * @param list<Node> $own
     * @param list<Node> $list
     */
    public function __construct(
        array $own,
        public ?CompoundNode $single,
        public array $list,
        private readonly ?int $skip = null,
    ) {
        parent::__construct($own);
    }

    /** @return Node[] */
    public function getOwnChildren(): array
    {
        return parent::getChildren();
    }

    protected function compiledProperties(): array
    {
        return ['single', 'list'];
    }

    protected function compilesItem(string $property, int|string $key): bool
    {
        return $property !== 'list' || $key !== $this->skip;
    }
}
