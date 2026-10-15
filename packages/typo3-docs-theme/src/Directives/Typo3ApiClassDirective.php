<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Directives;

use phpDocumentor\Guides\Nodes\CollectionNode;
use phpDocumentor\Guides\Nodes\DocumentNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\RestructuredText\Directives\BaseDirective;
use phpDocumentor\Guides\RestructuredText\Parser\BlockContext;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;
use phpDocumentor\Guides\RestructuredText\Parser\Productions\Rule;
use Psr\Log\LoggerInterface;
use T3Docs\Typo3DocsTheme\Api\Typo3ApiService;

/**
 * Shows a class, interface, trait, or enum of the TYPO3 API with its
 * members, read from api.typo3.org:
 *
 *     ..  typo3:api-class:: \TYPO3\CMS\Backend\Event\AddUserSettingsJavaScriptModulesEvent
 *
 * The directive writes the same php:class, php:method, and so on as the
 * generated snippets of t3docs/codesnippet, and parses them like an included
 * file. The anchors and the entries in objects.inv are thus the same as with
 * a generated snippet, and a page can replace its `..  include::` of the
 * snippet with this directive.
 *
 * The options are those of the snippet configuration:
 *
 * :members: Only these constants, properties, and methods, in this order.
 * :visibility: The visibilities to show, by default "public".
 * :include-internal: Also show an @internal type or member.
 * :exclude-deprecated: Leave out a deprecated method. By default it is shown
 *     with a notice, as in the generated snippets.
 * :include-constructor: Also show __construct().
 * :no-class-comment: Leave out the description of the type.
 * :no-member-comment: Leave out the descriptions of the members.
 * :no-parameters: Leave out the parameters and the return description.
 * :noindex: Do not register the type for links and the index.
 * :noindex-members: Do not register the members.
 */
final class Typo3ApiClassDirective extends BaseDirective
{
    public const NAME = 'typo3:api-class';

    private const INDENT = '    ';

    /** The type that is rendered, for self and static. */
    private string $currentClass = '';

    private bool $readonlyClass = false;

    /**
     * @param Rule<DocumentNode> $startingRule
     */
    public function __construct(
        private readonly Rule $startingRule,
        private readonly Typo3ApiService $typo3ApiService,
        private readonly LoggerInterface $logger,
    ) {}

    public function getName(): string
    {
        return self::NAME;
    }

    public function processNode(BlockContext $blockContext, Directive $directive): Node
    {
        $fqn = trim($directive->getData(), " \\\t");
        if ($fqn === '') {
            $this->logger->warning(
                sprintf('The %s directive needs the name of a class, interface, trait, or enum.', self::NAME),
                $blockContext->getLoggerInformation(),
            );
            return new CollectionNode([]);
        }

        $type = $this->typo3ApiService->getTypeApi($fqn);
        if ($type === null) {
            $this->logger->warning(
                sprintf('The TYPO3 API does not document "%s". Check the name, or whether the TYPO3 version of this manual has it.', $fqn),
                $blockContext->getLoggerInformation(),
            );
            return new CollectionNode([]);
        }

        $settings = new ApiClassSettings($directive);
        $this->currentClass = '\\' . trim($this->string($type, 'fqn'), '\\');
        $this->readonlyClass = in_array('readonly', $this->strings($type, 'modifiers'), true);
        if (is_string($type['internal'] ?? null) && !$settings->includeInternal) {
            $this->logger->warning(
                sprintf('"%s" is marked as @internal. Use the option :include-internal: to show it anyway.', $fqn),
                $blockContext->getLoggerInformation(),
            );
            return new CollectionNode([]);
        }

        $rst = $this->renderType($type, $settings, $blockContext);

        $currentDocument = $blockContext->getDocumentParserContext()->getDocument();
        $document = $this->startingRule->apply(new BlockContext($blockContext->getDocumentParserContext(), $rst));
        $blockContext->getDocumentParserContext()->setDocument($currentDocument);

        return new CollectionNode($document?->getChildren() ?? []);
    }

    /** @param array<mixed> $type */
    private function renderType(array $type, ApiClassSettings $settings, BlockContext $blockContext): string
    {
        $directive = match ($type['type'] ?? 'class') {
            'interface' => 'php:interface',
            'trait' => 'php:trait',
            'enum' => 'php:enum',
            default => 'php:class',
        };

        $lines = [];
        if (is_string($type['namespace'] ?? null) && $type['namespace'] !== '') {
            $lines[] = '..  php:namespace::  ' . $type['namespace'];
            $lines[] = '';
        }

        $lines[] = sprintf('..  %s:: %s', $directive, $this->string($type, 'name'));
        if ($settings->noindex) {
            $lines[] = self::INDENT . ':noindex:';
        }
        if ($directive === 'php:class') {
            foreach (array_intersect($this->strings($type, 'modifiers'), ['abstract', 'final']) as $modifier) {
                $lines[] = self::INDENT . ':' . $modifier . ':';
            }
        }
        $lines[] = '';

        if ($settings->includeClassComment) {
            $description = $this->description($type);
            if ($description !== '') {
                $lines[] = $this->indent($description, 1);
                $lines[] = '';
            }
        }

        $members = $this->selectMembers($type, $settings, $blockContext);
        foreach ($members['cases'] as $case) {
            $lines[] = $this->indent($this->renderCase($case, $settings), 1);
            $lines[] = '';
        }
        foreach ($members['constants'] as $constant) {
            $lines[] = $this->indent($this->renderConstant($constant, $settings), 1);
            $lines[] = '';
        }
        foreach ($members['properties'] as $property) {
            $lines[] = $this->indent($this->renderProperty($property, $settings), 1);
            $lines[] = '';
        }
        foreach ($members['methods'] as $method) {
            $lines[] = $this->indent($this->renderMethod($method, $settings), 1);
            $lines[] = '';
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * The members to show, by the options of the directive.
     *
     * @param array<mixed> $type
     * @return array{cases: list<array<mixed>>, constants: list<array<mixed>>, properties: list<array<mixed>>, methods: list<array<mixed>>}
     */
    private function selectMembers(array $type, ApiClassSettings $settings, BlockContext $blockContext): array
    {
        $selected = ['cases' => [], 'constants' => [], 'properties' => [], 'methods' => []];
        $available = [];
        foreach (['cases', 'constants', 'properties', 'methods'] as $kind) {
            foreach ($this->arrays($type, $kind) as $member) {
                if (is_string($member['name'] ?? null)) {
                    $available[$kind][$this->memberKey($kind, $member['name'])] = $member;
                }
            }
        }

        if ($settings->members === []) {
            foreach ($available as $kind => $members) {
                foreach ($members as $member) {
                    if ($this->isShown($kind, $member, $settings)) {
                        $selected[$kind][] = $member;
                    }
                }
            }

            return $selected;
        }

        foreach ($settings->members as $name) {
            $found = false;
            foreach (['methods', 'properties', 'constants', 'cases'] as $kind) {
                $member = $available[$kind][$this->memberKey($kind, $name)] ?? null;
                if ($member === null) {
                    continue;
                }

                $found = true;
                if ($this->isShown($kind, $member, $settings)) {
                    $selected[$kind][] = $member;
                }
                break;
            }

            if (!$found) {
                $this->logger->warning(
                    sprintf('"%s" has no constant, property, method, or case "%s".', $this->string($type, 'fqn'), $name),
                    $blockContext->getLoggerInformation(),
                );
            }
        }

        return $selected;
    }

    /** PHP does not distinguish between upper and lower case in the names of methods. */
    private function memberKey(string $kind, string $name): string
    {
        return $kind === 'methods' ? strtolower($name) : $name;
    }

    /** @param array<mixed> $member */
    private function isShown(string $kind, array $member, ApiClassSettings $settings): bool
    {
        if ($kind === 'cases') {
            return true;
        }

        if (!in_array($member['visibility'] ?? 'public', $settings->visibility, true)) {
            return false;
        }

        if (is_string($member['internal'] ?? null) && !$settings->includeInternal) {
            return false;
        }

        if ($kind === 'methods') {
            if (is_string($member['deprecated'] ?? null) && $settings->excludeDeprecated) {
                return false;
            }

            if (($member['name'] ?? '') === '__construct' && !$settings->includeConstructor) {
                return false;
            }
        }

        return true;
    }

    /** @param array<mixed> $case */
    private function renderCase(array $case, ApiClassSettings $settings): string
    {
        $lines = ['..  php:case:: ' . $this->string($case, 'name')];

        return $this->renderMember($lines, $case, $settings);
    }

    /** @param array<mixed> $constant */
    private function renderConstant(array $constant, ApiClassSettings $settings): string
    {
        $lines = ['..  php:const:: ' . $this->string($constant, 'name')];
        $lines[] = self::INDENT . ':' . $this->string($constant, 'visibility', 'public') . ':';

        $value = $this->string($constant, 'value');
        $valueLine = '';
        if ($value !== '' && !str_contains($value, '`')) {
            $type = $this->string($constant, 'type', $this->typeOfValue($value));
            $valueLine = ':php:`' . $this->escape($value) . '`' . ($type !== '' ? ', type ' . $this->escape($type) : '');
        }

        return $this->renderMember($lines, $constant, $settings, valueLine: $valueLine);
    }

    /** @param array<mixed> $property */
    private function renderProperty(array $property, ApiClassSettings $settings): string
    {
        $lines = ['..  php:attr:: ' . $this->string($property, 'name')];
        $modifiers = array_intersect($this->strings($property, 'modifiers'), ['static', 'readonly']);
        if ($this->readonlyClass && !in_array('readonly', $modifiers, true)) {
            $modifiers[] = 'readonly';
        }
        foreach ([$this->string($property, 'visibility', 'public'), ...$modifiers] as $modifier) {
            $lines[] = self::INDENT . ':' . $modifier . ':';
        }

        return $this->renderMember($lines, $property, $settings);
    }

    /** @param array<mixed> $method */
    private function renderMethod(array $method, ApiClassSettings $settings): string
    {
        $parameters = [];
        $signature = [];
        foreach ($this->arrays($method, 'parameters') as $parameter) {
            $name = '$' . $this->string($parameter, 'name');
            $signature[] = sprintf(
                '%s %s%s%s%s',
                $this->type($this->string($parameter, 'type', 'mixed')),
                ($parameter['variadic'] ?? false) === true ? '...' : '',
                ($parameter['byReference'] ?? false) === true ? '&' : '',
                $name,
                is_string($parameter['default'] ?? null) ? ' = ' . $parameter['default'] : '',
            );
            $description = $this->escape($this->oneLine($this->string($parameter, 'description')));
            $parameters[] = sprintf(
                ':param %s: %s%s',
                $name,
                $description !== '' ? $description : 'the ' . $this->string($parameter, 'name'),
                is_string($parameter['default'] ?? null) ? ', default: ' . $parameter['default'] : '',
            );
        }

        $returns = is_array($method['returns'] ?? null) ? $method['returns'] : [];
        $returnType = $this->type($this->string($returns, 'type'));
        $lines = [sprintf('..  php:method:: %s(%s)', $this->string($method, 'name'), implode(', ', $signature))];
        if ($returnType !== '' && $returnType !== 'void' && ($method['name'] ?? '') !== '__construct') {
            // The php domain parses the field as reStructuredText.
            $lines[] = self::INDENT . ':returns: `' . $this->escape($returnType) . '`';
        }
        foreach (array_intersect($this->strings($method, 'modifiers'), ['static', 'abstract', 'final']) as $modifier) {
            $lines[] = self::INDENT . ':' . $modifier . ':';
        }
        if (in_array($method['visibility'] ?? 'public', ['protected', 'private'], true)) {
            $lines[] = self::INDENT . ':' . $method['visibility'] . ':';
        }

        $text = $this->renderMember($lines, $method, $settings, deprecationNotice: true);
        if (!$settings->includeMethodParameters) {
            return $text;
        }

        $fields = $parameters;
        $returnDescription = $this->escape($this->oneLine($this->string($returns, 'description')));
        if ($returnDescription !== '') {
            $fields[] = ':Return description: ' . ucfirst($returnDescription);
        }

        if ($fields === []) {
            return $text;
        }

        return $text . $this->indent(implode("\n", $fields), 1) . "\n\n";
    }

    /**
     * @param list<string> $lines the directive and its options
     * @param array<mixed> $member
     */
    private function renderMember(array $lines, array $member, ApiClassSettings $settings, bool $deprecationNotice = false, string $valueLine = ''): string
    {
        if ($settings->noindexMembers) {
            $lines[] = self::INDENT . ':noindex:';
        }
        $lines[] = '';

        if ($settings->includeMemberComment) {
            if ($valueLine !== '') {
                $lines[] = self::INDENT . $valueLine;
                $lines[] = '';
            }
            $description = $this->description($member, $deprecationNotice);
            if ($description !== '') {
                $lines[] = $this->indent($description, 1);
                $lines[] = '';
            }
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * The summary and the description of a docblock as reStructuredText.
     *
     * @param array<mixed> $element
     */
    private function description(array $element, bool $deprecationNotice = false): string
    {
        $parts = [];
        if ($deprecationNotice && is_string($element['deprecated'] ?? null)) {
            $parts[] = trim('**Deprecated:** ' . $element['deprecated']);
        }
        foreach (['summary', 'description'] as $key) {
            $text = trim($this->string($element, $key));
            if ($text !== '') {
                $parts[] = $text;
            }
        }

        return $this->docBlockToRst(implode("\n\n", $parts));
    }

    /**
     * Docblocks are written in Markdown: turn fenced code into code blocks,
     * inline tags such as {@see \Foo} into literals, and keep the
     * backslashes of class names, which reStructuredText would drop.
     */
    private function docBlockToRst(string $text): string
    {
        $parts = preg_split('/```[a-z]*\n?([\s\S]*?)```/', $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$text];
        $rst = '';
        foreach ($parts as $index => $part) {
            $rst .= $index % 2 === 1
                ? "\n\n..  code-block:: php\n\n" . $this->indent(rtrim($part), 1) . "\n\n"
                : $this->inlineTags($this->escape($part));
        }

        return trim($rst);
    }

    private function inlineTags(string $text): string
    {
        return (string)preg_replace_callback(
            '/{@(?:see|link)\s+([^\s}]+)(?:\s+([^}]*))?}/',
            static function (array $matches): string {
                $target = '``' . str_replace('\\\\', '\\', $matches[1]) . '``';

                return ($matches[2] ?? '') !== '' ? $matches[2] . ' (' . $target . ')' : $target;
            },
            $text,
        );
    }

    /** Escapes the backslashes of text that is not reStructuredText. */
    private function escape(string $text): string
    {
        return str_replace('\\', '\\\\', $text);
    }

    /** The type of a constant without a declared type, as far as its value tells. */
    private function typeOfValue(string $value): string
    {
        return match (true) {
            preg_match('/^([\'"]).*\1$/s', $value) === 1 => 'string',
            preg_match('/^-?\d+$/', $value) === 1 => 'int',
            preg_match('/^-?\d*\.\d+$/', $value) === 1 => 'float',
            in_array(strtolower($value), ['true', 'false'], true) => 'bool',
            str_starts_with($value, '[') => 'array',
            default => '',
        };
    }

    /** A type with self, static, and $this replaced by the type that is rendered. */
    private function type(string $type): string
    {
        return (string)preg_replace('/(?<![\\\\\w$])(?:self|static|\$this)(?![\\\\\w])/', $this->currentClass, $type);
    }

    private function indent(string $text, int $levels): string
    {
        $prefix = str_repeat(self::INDENT, $levels);

        return implode("\n", array_map(
            static fn(string $line): string => $line === '' ? '' : $prefix . $line,
            explode("\n", rtrim($text)),
        ));
    }

    private function oneLine(string $text): string
    {
        return trim((string)preg_replace('/\s+/', ' ', $text));
    }

    /** @param array<mixed> $data */
    private function string(array $data, string $key, string $default = ''): string
    {
        $value = $data[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : $default;
    }

    /**
     * @param array<mixed> $data
     * @return list<string>
     */
    private function strings(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        return is_array($value) ? array_values(array_filter($value, is_string(...))) : [];
    }

    /**
     * @param array<mixed> $data
     * @return list<array<mixed>>
     */
    private function arrays(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        return is_array($value) ? array_values(array_filter($value, is_array(...))) : [];
    }
}
