<?php

declare(strict_types=1);

use phpDocumentor\Guides\Nodes\AnchorNode;
use phpDocumentor\Guides\Nodes\AnnotationListNode;
use phpDocumentor\Guides\Nodes\CitationNode;
use phpDocumentor\Guides\Nodes\CodeNode;
use phpDocumentor\Guides\Nodes\CollectionNode;
use phpDocumentor\Guides\RestructuredText\Nodes\ContainerNode;
use phpDocumentor\Guides\Nodes\DefinitionListNode;
use phpDocumentor\Guides\Nodes\DefinitionLists\DefinitionListItemNode;
use phpDocumentor\Guides\Nodes\DefinitionLists\DefinitionNode;
use phpDocumentor\Guides\Nodes\FieldLists\FieldListItemNode;
use phpDocumentor\Guides\Nodes\DocumentNode;
use phpDocumentor\Guides\Nodes\FieldListNode;
use phpDocumentor\Guides\Nodes\FigureNode;
use phpDocumentor\Guides\Nodes\FootnoteNode;
use phpDocumentor\Guides\Nodes\ImageNode;
use phpDocumentor\Guides\Nodes\MathNode;
use phpDocumentor\Guides\Nodes\Inline\AbbreviationInlineNode;
use phpDocumentor\Guides\Nodes\Inline\CitationInlineNode;
use phpDocumentor\Guides\Nodes\Inline\DocReferenceNode;
use phpDocumentor\Guides\Nodes\Inline\EmphasisInlineNode;
use phpDocumentor\Guides\Nodes\Inline\FootnoteInlineNode;
use phpDocumentor\Guides\Nodes\Inline\GenericTextRoleInlineNode;
use phpDocumentor\Guides\Nodes\Inline\HyperLinkNode;
use phpDocumentor\Guides\Nodes\Inline\ImageInlineNode;
use phpDocumentor\Guides\Nodes\Inline\LiteralInlineNode;
use phpDocumentor\Guides\Nodes\Inline\NewlineInlineNode;
use phpDocumentor\Guides\Nodes\Inline\PlainTextInlineNode;
use phpDocumentor\Guides\Nodes\Inline\ReferenceNode;
use phpDocumentor\Guides\Nodes\Inline\StrongInlineNode;
use phpDocumentor\Guides\Nodes\Inline\VariableInlineNode;
use phpDocumentor\Guides\Nodes\Inline\WhitespaceInlineNode;
use phpDocumentor\Guides\Nodes\InlineCompoundNode;
use phpDocumentor\Guides\Nodes\ListItemNode;
use phpDocumentor\Guides\Nodes\ListNode;
use phpDocumentor\Guides\Nodes\LiteralBlockNode;
use phpDocumentor\Guides\Nodes\Metadata\AddressNode;
use phpDocumentor\Guides\Nodes\Metadata\AuthorNode;
use phpDocumentor\Guides\Nodes\Metadata\AuthorsNode;
use phpDocumentor\Guides\Nodes\Metadata\ContactNode;
use phpDocumentor\Guides\Nodes\Metadata\CopyrightNode;
use phpDocumentor\Guides\Nodes\Metadata\DateNode;
use phpDocumentor\Guides\Nodes\Metadata\MetaNode;
use phpDocumentor\Guides\Nodes\Metadata\NoCommentsNode;
use phpDocumentor\Guides\Nodes\Metadata\NoSearchNode;
use phpDocumentor\Guides\Nodes\Metadata\OrganizationNode;
use phpDocumentor\Guides\Nodes\Metadata\OrphanNode;
use phpDocumentor\Guides\Nodes\Metadata\RevisionNode;
use phpDocumentor\Guides\Nodes\Metadata\TocDepthNode;
use phpDocumentor\Guides\Nodes\Metadata\TopicNode;
use phpDocumentor\Guides\Nodes\Metadata\VersionNode;
use phpDocumentor\Guides\Nodes\ParagraphNode;
use phpDocumentor\Guides\Nodes\QuoteNode;
use phpDocumentor\Guides\Nodes\SectionNode;
use phpDocumentor\Guides\Nodes\SeparatorNode;
use phpDocumentor\Guides\Nodes\TableNode;
use phpDocumentor\Guides\Nodes\TitleNode;
use phpDocumentor\Guides\RestructuredText\Nodes\ConfvalNode;
use phpDocumentor\Guides\RestructuredText\Nodes\OptionNode;
use T3Docs\Typo3DocsTheme\Nodes\ConfvalMenuNode;
use phpDocumentor\Guides\RestructuredText\Nodes\SidebarNode;
use phpDocumentor\Guides\RestructuredText\Nodes\VersionChangeNode;

return [
    AnchorNode::class => 'inline/anchor.md.twig',
    FigureNode::class => 'body/figure.md.twig',
    MetaNode::class => 'structure/header/blank.md.twig',
    ParagraphNode::class => 'body/paragraph.md.twig',
    QuoteNode::class => 'body/quote.md.twig',
    ConfvalNode::class => 'body/directive/confval.md.twig',
    OptionNode::class => 'body/directive/option.md.twig',
    ConfvalMenuNode::class => 'body/directive/confval-menu.md.twig',
    SidebarNode::class => 'body/sidebar.md.twig',
    VersionChangeNode::class => 'body/version-change.md.twig',
    SeparatorNode::class => 'body/separator.md.twig',
    TitleNode::class => 'structure/header-title.md.twig',
    SectionNode::class => 'structure/section.md.twig',
    DocumentNode::class => 'structure/document.md.twig',
    ImageNode::class => 'body/image.md.twig',
    MathNode::class => 'body/math.md.twig',
    CodeNode::class => 'body/code.md.twig',
    CollectionNode::class => 'body/collection.md.twig',
    ContainerNode::class => 'body/container.md.twig',
    DefinitionListNode::class => 'body/definition-list.md.twig',
    DefinitionNode::class => 'body/definition.md.twig',
    FieldListNode::class => 'body/field-list.md.twig',
    FieldListItemNode::class => 'body/field-list-item.md.twig',
    DefinitionListItemNode::class => 'body/definition-list-item.md.twig',
    ListNode::class => 'body/list/list.md.twig',
    ListItemNode::class => 'body/list/list-item.md.twig',
    LiteralBlockNode::class => 'body/literal-block.md.twig',
    CitationNode::class => 'body/citation.md.twig',
    FootnoteNode::class => 'body/footnote.md.twig',
    AnnotationListNode::class => 'body/annotation-list.md.twig',
    TableNode::class => 'body/table.md.twig',
    // Inline
    ImageInlineNode::class => 'inline/image.md.twig',
    AbbreviationInlineNode::class => 'inline/textroles/abbreviation.md.twig',
    CitationInlineNode::class => 'inline/citation.md.twig',
    DocReferenceNode::class => 'inline/link.md.twig',
    EmphasisInlineNode::class => 'inline/emphasis.md.twig',
    FootnoteInlineNode::class => 'inline/footnote.md.twig',
    HyperLinkNode::class => 'inline/link.md.twig',
    LiteralInlineNode::class => 'inline/literal.md.twig',
    NewlineInlineNode::class => 'inline/newline.md.twig',
    WhitespaceInlineNode::class => 'inline/nbsp.md.twig',
    PlainTextInlineNode::class => 'inline/plain-text.md.twig',
    ReferenceNode::class => 'inline/link.md.twig',
    StrongInlineNode::class => 'inline/strong.md.twig',
    VariableInlineNode::class => 'inline/plain-text.md.twig',
    GenericTextRoleInlineNode::class => 'inline/textroles/generic.md.twig',
    InlineCompoundNode::class => 'inline/inline-node.md.twig',
    // Document metadata has no Markdown representation; it is dropped rather
    // than rendered, the way the RST theme drops what has no RST form.
    AddressNode::class => 'structure/header/blank.md.twig',
    AuthorNode::class => 'structure/header/blank.md.twig',
    AuthorsNode::class => 'structure/header/blank.md.twig',
    ContactNode::class => 'structure/header/blank.md.twig',
    CopyrightNode::class => 'structure/header/blank.md.twig',
    DateNode::class => 'structure/header/blank.md.twig',
    NoCommentsNode::class => 'structure/header/blank.md.twig',
    NoSearchNode::class => 'structure/header/blank.md.twig',
    OrganizationNode::class => 'structure/header/blank.md.twig',
    OrphanNode::class => 'structure/header/blank.md.twig',
    RevisionNode::class => 'structure/header/blank.md.twig',
    TocDepthNode::class => 'structure/header/blank.md.twig',
    TopicNode::class => 'structure/header/blank.md.twig',
    VersionNode::class => 'structure/header/blank.md.twig',
];
