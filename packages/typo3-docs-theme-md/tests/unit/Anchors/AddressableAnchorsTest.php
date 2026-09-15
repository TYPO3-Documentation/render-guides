<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsThemeMd\Tests\Unit\Anchors;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use phpDocumentor\Guides\Meta\InternalTarget;
use phpDocumentor\Guides\Nodes\ProjectNode;
use phpDocumentor\Guides\Nodes\SectionNode;
use T3Docs\Typo3DocsThemeMd\Anchors\AddressableAnchors;

/**
 * The rule both sides of the single-file output ask before they write an anchor
 * or turn a link inward. If it ever answers differently for the same input,
 * anchors and links stop meeting.
 */
#[CoversClass(AddressableAnchors::class)]
final class AddressableAnchorsTest extends TestCase
{
    public function testARegisteredLabelIsAddressable(): void
    {
        $projectNode = new ProjectNode();
        $projectNode->addLinkTarget('my-label', new InternalTarget('some/document', 'my-label'));

        self::assertTrue(AddressableAnchors::isAddressable($projectNode, 'my-label'));
    }

    /**
     * The whole point: headings repeat, so addLinkTarget() lets a second
     * "std:title" of the same name through where it would refuse a label. An
     * anchor built from one would resolve to whichever copy came first.
     */
    public function testATitleIsNotAddressable(): void
    {
        $projectNode = new ProjectNode();
        $projectNode->addLinkTarget(
            'configuration',
            new InternalTarget('some/document', 'configuration', null, SectionNode::STD_TITLE),
        );

        self::assertFalse(AddressableAnchors::isAddressable($projectNode, 'configuration'));
    }

    /**
     * Not only sections are targets: a PHP class, a confval or a console
     * command registers under a link type of its own, and each of those is
     * unique because addLinkTarget() refuses a duplicate.
     */
    public function testANonSectionLinkTypeIsAddressable(): void
    {
        $projectNode = new ProjectNode();
        $projectNode->addLinkTarget(
            'typo3-cms-core-site-entity-site',
            new InternalTarget('some/document', 'typo3-cms-core-site-entity-site', null, 'php:class'),
        );

        self::assertTrue(AddressableAnchors::isAddressable($projectNode, 'typo3-cms-core-site-entity-site'));
    }

    /**
     * A name registered as a title and, elsewhere, as a label is addressable:
     * the label is what makes it unambiguous, and the title alongside it
     * changes nothing.
     */
    public function testALabelWinsOverATitleOfTheSameName(): void
    {
        $projectNode = new ProjectNode();
        $projectNode->addLinkTarget(
            'introduction',
            new InternalTarget('some/document', 'introduction', null, SectionNode::STD_TITLE),
        );
        $projectNode->addLinkTarget('introduction', new InternalTarget('other/document', 'introduction'));

        self::assertTrue(AddressableAnchors::isAddressable($projectNode, 'introduction'));
    }

    public function testAnUnknownAnchorIsNotAddressable(): void
    {
        self::assertFalse(AddressableAnchors::isAddressable(new ProjectNode(), 'never-registered'));
    }

    public function testTheEmptyAnchorIsNotAddressable(): void
    {
        self::assertFalse(AddressableAnchors::isAddressable(new ProjectNode(), ''));
    }
}
