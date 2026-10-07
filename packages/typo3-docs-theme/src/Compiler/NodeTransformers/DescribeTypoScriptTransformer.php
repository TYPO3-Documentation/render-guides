<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Compiler\NodeTransformers;

use phpDocumentor\Guides\Compiler\CompilerContextInterface;
use phpDocumentor\Guides\Compiler\NodeTransformer;
use phpDocumentor\Guides\Nodes\Node;
use Psr\Log\LoggerInterface;
use T3Docs\Typo3DocsTheme\Nodes\Inline\CodeInlineNode;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;
use T3Docs\Typo3DocsTheme\TextRoles\TSconfigTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\TypoScriptTextTextRole;
use T3Docs\Typo3DocsTheme\TypoScriptReference\ExternalTypoScript;
use T3Docs\Typo3DocsTheme\TypoScriptReference\LocalTypoScript;
use T3Docs\Typo3DocsTheme\TypoScriptReference\NamedConfval;

use function explode;
use function in_array;
use function sprintf;
use function str_contains;
use function str_ends_with;

/**
 * Tells a ":typoscript:" or ":tsconfig:" role that names an option by its
 * full path, or a ":typoscript:" role that names an object type, what it is
 * and where it is documented, as ":fluid:" does for a ViewHelper. A path is
 * found by the option's anchor or by the path the option declares.
 * @see \T3Docs\Typo3DocsTheme\TypoScriptReference\OptionPaths
 *
 * What the manual documents itself is described from its own pages, which is
 * what the TypoScript reference does; anything else from the reference. Only
 * a full path is looked up: "wrap" alone names eleven options, and a wrong
 * description is worse than none. What neither knows stays TypoScript code.
 *
 * @implements NodeTransformer<CodeInlineNode>
 * @phpstan-import-type Option from ExternalTypoScript
 */
final class DescribeTypoScriptTransformer implements NodeTransformer
{
    private const OPTION = 'TypoScript option';

    public function __construct(
        private readonly LocalTypoScript $localTypoScript,
        private readonly ExternalTypoScript $externalTypoScript,
        private readonly Typo3DocsThemeSettings $themeSettings,
        private readonly LoggerInterface $logger,
    ) {}

    public function enterNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        if (!$node instanceof CodeInlineNode) {
            return $node;
        }

        $info = $node->getInfo();
        $named = $info[NamedConfval::INFO] ?? '';
        if ($named !== '') {
            $this->describeNamed($node, $named, $info[NamedConfval::ROLE_INFO] ?? 'typoscript', $compilerContext);

            return $node;
        }

        foreach ([TypoScriptTextTextRole::OPTION_INFO => 'TypoScript option', TSconfigTextRole::OPTION_INFO => 'TSconfig option'] as $key => $kind) {
            $path = $info[$key] ?? '';
            if ($path === '') {
                continue;
            }
            $option = $this->localTypoScript->findOption($path) ?? $this->externalTypoScript->findOption($path);
            if ($option !== null) {
                $this->describe($node, $option, $kind);
            }

            return $node;
        }

        // An object type or a function the reference documents as an option
        // of its own, with what it does. A function named alone, "stdWrap",
        // is found this way only.
        $type = $info[TypoScriptTextTextRole::OBJECT_INFO] ?? '';
        $word = $type !== '' ? $type : ($info[TypoScriptTextTextRole::WORD_INFO] ?? '');
        if ($word !== '') {
            $option = $this->localTypoScript->findWord($word) ?? $this->externalTypoScript->findWord($word);
            if ($option !== null) {
                $this->describe($node, $option, $type !== '' ? 'TypoScript object type' : self::OPTION);

                return $node;
            }
        }

        // Otherwise an object type by the headline that documents it.
        if ($type !== '') {
            $objectType = $this->localTypoScript->findObjectType($type, $compilerContext->getProjectNode())
                ?? $this->externalTypoScript->findObjectType($type);
            if ($objectType !== null) {
                // A headline that is the type alone says nothing more.
                $details = $objectType['title'] === $type ? '' : 'Documented under "' . $objectType['title'] . '".';
                $node->describeAs('TypoScript object type', $details, $this->info('', $objectType['url']));
            }
        }

        return $node;
    }

    /**
     * The confval a role names in angle brackets, in this manual or in the one
     * its interlink key names: "module.tx_extbase <t3coreapi:some-key>".
     *
     * A name the manual does not document is reported, as for ":confval:". A
     * manual that could not be read is not: the author can do nothing about it.
     */
    private function describeNamed(CodeInlineNode $node, string $named, string $role, CompilerContextInterface $compilerContext): void
    {
        [$manual, $name] = str_contains($named, ':') ? explode(':', $named, 2) : ['', $named];
        $own = $this->themeSettings->getSettings('interlink_shortcode');
        if ($manual === '' || $manual === $own) {
            [$loaded, $option] = [true, $this->localTypoScript->findNamed($name)];
        } else {
            [$loaded, $option] = $this->externalTypoScript->findNamed($manual, $name);
        }

        if ($option === null) {
            if ($loaded) {
                $this->logger->warning(sprintf(
                    'The :%s: role "%s" names the confval "%s", which %s does not document.',
                    $role,
                    $node->getValue(),
                    $named,
                    $manual === '' || $manual === $own ? 'this manual' : 'the manual ' . $manual,
                ), $compilerContext->getLoggerInformation());
            }

            return;
        }

        $this->describe($node, $option, $role === 'tsconfig' ? 'TSconfig option' : self::OPTION);
    }

    /**
     * Says what an option is. An object or a function is called so, and its
     * type, which is that kind -- "toplevel", "cObject", "function" -- is not
     * repeated.
     *
     * @param Option $option
     */
    private function describe(CodeInlineNode $node, array $option, string $kind): void
    {
        $kind = match (true) {
            $option['type'] === 'function' => 'TypoScript function',
            $option['type'] === 'toplevel' => 'Top-level TypoScript object',
            in_array($option['type'], ['cObject', 'menu object', 'GIFBUILDER object'], true) => 'TypoScript object type',
            default => $kind,
        };
        $flags = str_ends_with($kind, ' option') && $option['type'] !== '' ? 'Type: ' . $option['type'] : '';
        $node->describeAs($kind, $option['summary'], $this->info($flags, $option['url']));
    }

    /** @return array<string, string> */
    private function info(string $flags, string $url): array
    {
        $info = [];
        if ($flags !== '') {
            $info['flags'] = $flags;
        }
        if ($url !== '') {
            $info['url'] = $url;
        }

        return $info;
    }

    public function leaveNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        return $node;
    }

    public function supports(Node $node): bool
    {
        return $node instanceof CodeInlineNode;
    }

    public function getPriority(): int
    {
        // After CollectTypoScriptOptionsTransformer
        return 2000;
    }
}
