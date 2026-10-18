<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Compiler\NodeTransformers;

use phpDocumentor\Guides\Compiler\CompilerContext;
use phpDocumentor\Guides\Compiler\CompilerContextInterface;
use phpDocumentor\Guides\Compiler\NodeTransformer;
use phpDocumentor\Guides\Nodes\CompoundNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\Nodes\Table\TableColumn;
use phpDocumentor\Guides\Nodes\Table\TableRow;
use phpDocumentor\Guides\Nodes\TableNode;
use phpDocumentor\Guides\RestructuredText\Compiler\Passes\DirectiveProcessPass;
use phpDocumentor\Guides\RestructuredText\Nodes\DirectiveNode;

use function array_values;
use function count;

/**
 * Creates the directives in the cells of a table, such as an image or a note.
 *
 * Since guides 1.11, a directive such as "image" or "note" is a DirectiveNode
 * after parsing, and DirectiveProcessPass creates its node when the document
 * is compiled. The compiler only visits the children of a node, and a
 * TableNode keeps its cells in its rows, so a directive in a cell stayed a
 * DirectiveNode: an image rendered as nothing, a note as its text only.
 *
 * Remove this transformer with the guides update that contains
 * phpDocumentor/guides#1440, where the cells are the children of the table.
 *
 * @implements NodeTransformer<TableNode>
 */
final class TableCellDirectivesTransformer implements NodeTransformer
{
    public function __construct(
        private readonly DirectiveProcessPass $directiveProcessPass,
    ) {}

    public function enterNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        return $node;
    }

    public function leaveNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        // DirectiveProcessPass needs the compiler's own context, which is the one it passes.
        if (!$node instanceof TableNode || !$compilerContext instanceof CompilerContext) {
            return $node;
        }

        return $this->table($node, $compilerContext);
    }

    private function table(TableNode $table, CompilerContext $compilerContext): TableNode
    {
        $changed = false;
        $headers = $this->rows($table->getHeaders(), $compilerContext, $changed);
        $data = $this->rows($table->getData(), $compilerContext, $changed);
        if (!$changed) {
            return $table;
        }

        $result = (new TableNode($data, $headers, $table->getColumnWidth()))->withOptions($table->getOptions());
        $result->setClasses($table->getClasses());

        return $result;
    }

    /**
     * @param TableRow[] $rows
     *
     * @return TableRow[]
     */
    private function rows(array $rows, CompilerContext $compilerContext, bool &$changed): array
    {
        $result = [];
        foreach ($rows as $key => $row) {
            $columns = [];
            foreach ($row->getColumns() as $columnKey => $column) {
                $children = $this->children($column->getChildren(), $compilerContext);
                if ($children === $column->getChildren()) {
                    $columns[$columnKey] = $column;
                    continue;
                }

                $changed = true;
                $columns[$columnKey] = new TableColumn($column->getContent(), $column->getColSpan(), $children, $column->getRowSpan());
            }
            $result[$key] = new TableRow($columns);
        }

        return $result;
    }

    /**
     * Creates the directives below these nodes, the innermost first, as the
     * compiler does.
     *
     * @param Node[] $nodes
     *
     * @return Node[]
     */
    private function children(array $nodes, CompilerContext $compilerContext): array
    {
        $result = [];
        foreach ($nodes as $key => $node) {
            $created = $this->node($node, $compilerContext);
            if ($created !== null) {
                $result[$key] = $created;
            }
        }

        return $result === $nodes ? $nodes : array_values($result);
    }

    private function node(Node $node, CompilerContext $compilerContext): Node|null
    {
        if ($node instanceof TableNode) {
            return $this->table($node, $compilerContext);
        }
        if ($node instanceof CompoundNode) {
            $children = $this->children($node->getChildren(), $compilerContext);
            if ($children !== $node->getChildren()) {
                $node = $this->withChildren($node, $children);
            }
        }
        if ($node instanceof DirectiveNode) {
            return $this->directiveProcessPass->leaveNode($node, $compilerContext);
        }

        return $node;
    }

    /**
     * @param CompoundNode<Node> $node
     * @param Node[] $children
     *
     * @return CompoundNode<Node>
     */
    private function withChildren(CompoundNode $node, array $children): CompoundNode
    {
        for ($key = count($node->getChildren()) - 1; $key >= 0; $key--) {
            $node = $node->removeNode($key);
        }
        foreach ($children as $child) {
            $node->addChildNode($child);
        }

        return $node;
    }

    public function supports(Node $node): bool
    {
        return $node instanceof TableNode;
    }

    public function getPriority(): int
    {
        // The priority of DirectiveProcessPass, which creates the other directives.
        return 100;
    }
}
