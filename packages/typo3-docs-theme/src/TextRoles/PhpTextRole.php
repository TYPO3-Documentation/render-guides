<?php

namespace T3Docs\Typo3DocsTheme\TextRoles;

use phpDocumentor\Guides\Nodes\Inline\InlineNode;
use phpDocumentor\Guides\RestructuredText\Parser\DocumentParserContext;
use phpDocumentor\Guides\RestructuredText\TextRoles\TextRole;
use T3Docs\Typo3DocsTheme\Api\Typo3ApiService;
use T3Docs\Typo3DocsTheme\Inventory\Typo3VersionService;
use T3Docs\Typo3DocsTheme\Nodes\Inline\CodeInlineNode;

final class PhpTextRole implements TextRole
{
    /**
     * @see https://regex101.com/r/LN5Ick/1
     */
    final public const CLASS_NAME_PATTERN_REGEX = '/^(\\\\)?[A-Za-z_][A-Za-z0-9_]*(\\\\[A-Za-z_][A-Za-z0-9_]*)*$/';

    /**
     * A namespaced class followed by "::" or "->" and a member of it -- a
     * method, property, constant or enum case. What follows the operator is
     * taken as written, arguments and all.
     */
    private const MEMBER_PATTERN_REGEX = '/^(\\\\?[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)+)((?:::|->).+)$/s';


    public function __construct(
        private readonly Typo3ApiService $typo3ApiService,
        private readonly Typo3VersionService $typo3VersionService,
    ) {}

    public function getName(): string
    {
        return 'php';
    }

    public function getAliases(): array
    {
        return [
            'php-short',
            'php-namespace',
        ];
    }

    public function processNode(DocumentParserContext $documentParserContext, string $role, string $content, string $rawContent): InlineNode
    {
        $fqn = [];
        $rawContent = trim($rawContent);
        $className = $rawContent;
        $member = '';
        if (preg_match(self::MEMBER_PATTERN_REGEX, $rawContent, $matches) === 1) {
            [, $className, $member] = $matches;
        }
        if ($role === 'php-namespace' && $this->isClassName($rawContent, $fqn)) {
            return $this->getNamespaceCodeNode('\\' . ltrim($rawContent, '\\'));
        }
        if (str_contains($className, '\\') && $this->isClassName($className, $fqn)) {
            if (!str_starts_with($className, '\\')) {
                $className = '\\' . $className;
            }
            $type = 'class or interface';
            $apiInfo = $this->typo3ApiService->getClassInfo($className);
            // Not a type the API knows, but the namespace of some: a class
            // role that names a namespace is a namespace, not a class.
            if ($apiInfo === [] && $member === '' && $this->typo3ApiService->isNamespace($className)) {
                return $this->getNamespaceCodeNode($className);
            }
            $name = $className;
            if ($role === 'php-short') {
                $shortName = $fqn[2] ?? '';
                $name = ltrim($shortName !== '' ? $shortName : $className, '\\');
            }
            return $this->getClassCodeNode($className, $apiInfo, $role, $name . $member, $type, $member);
        }
        if (str_starts_with($rawContent, '$GLOBALS[\'TYPO3_CONF_VARS\']')) {
            return $this->getTypo3ConfVarCodeNode($rawContent);
        }
        if (str_starts_with($rawContent, '$GLOBALS[\'TCA\']')) {
            return $this->getTcaCodeNode($rawContent);
        }
        if (str_starts_with($rawContent, '$GLOBALS[\'TSFE\']')) {
            return $this->getTsfeCodeNode($rawContent);
        }
        if ($rawContent === 'E_USER_DEPRECATED') {
            return $this->getDeprecationErrorCodeNode($rawContent);
        }
        return new CodeInlineNode($rawContent, 'Code written in PHP', 'Dynamic server-side scripting language.');
    }

    /**
     * A namespace, written in full whatever the role: its last segment alone
     * says too little. It carries no "fqn", which is what makes the class
     * index list a node and the popup offer a use statement.
     */
    private function getNamespaceCodeNode(string $namespace): CodeInlineNode
    {
        $info = ['signature' => 'namespace ' . ltrim($namespace, '\\')];
        if ($this->typo3ApiService->isNamespace($namespace)) {
            return new CodeInlineNode(
                $namespace,
                'PHP namespace',
                'A namespace of the TYPO3 API. Its classes and interfaces are listed in the API documentation.',
                [...$info, 'url' => $this->typo3ApiService->getNamespaceUrl($namespace)],
            );
        }
        if (str_starts_with($namespace, '\\MyVendor') || str_starts_with($namespace, '\\Vendor')) {
            return new CodeInlineNode(
                $namespace,
                'PHP namespace',
                'This namespace is commonly used in examples. Replace it with your own vendor and namespace on implementation.',
                $info,
            );
        }
        return new CodeInlineNode($namespace, 'PHP namespace', '', $info);
    }

    private function getDeprecationErrorCodeNode(string $rawContent): CodeInlineNode
    {
        return new CodeInlineNode(
            $rawContent,
            'Deprecation error',
            'Deprecation errors are by default not logged, however you can enable logging them (see link) on development systems.
                ',
            ['url' => 'https://docs.typo3.org/m/typo3/reference-coreapi/' . $this->typo3VersionService->getPreferredVersion() . '/en-us/ApiOverview/Deprecation/Index.html#deprecation_enable_errors']
        );
    }

    private function getTsfeCodeNode(string $rawContent): CodeInlineNode
    {
        return new CodeInlineNode(
            $rawContent,
            'TypoScript&shy;FrontendController',
            'TSFE is short for \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController,
                    a class which exists in the system extension EXT:frontend.
                    It is available as global array $GLOBALS[\'TSFE\'] in PHP.
                ',
            ['url' => 'https://docs.typo3.org/m/typo3/reference-coreapi/' . $this->typo3VersionService->getPreferredVersion() . '/en-us/ApiOverview/TSFE/Index.html']
        );
    }

    private function getTcaCodeNode(string $rawContent): CodeInlineNode
    {
        return new CodeInlineNode(
            $rawContent,
            'Table Configuration Array',
            'The TCA - Table Configuration Array - is a layer on
                top of the database tables that TYPO3 can operate on.
                It should be defined within the folder Configuration/TCA in an
                extension.
                ',
            ['url' => 'https://docs.typo3.org/m/typo3/reference-tca/' . $this->typo3VersionService->getPreferredVersion() . '/en-us/']
        );
    }

    private function getTypo3ConfVarCodeNode(string $rawContent): CodeInlineNode
    {
        return new CodeInlineNode(
            $rawContent,
            'Global PHP configuration',
            'The main configuration is achieved via a set of global
                settings stored in a global array called $GLOBALS[\'TYPO3_CONF_VARS\'].
                They are commonly set in config/system/settings.php, config/system/additional.php,
                or the ext_localconf.php file of an extension. ',
            ['url' => 'https://docs.typo3.org/m/typo3/reference-coreapi/' . $this->typo3VersionService->getPreferredVersion() . '/en-us/Configuration/Typo3ConfVars/Index.html']
        );
    }

    /**
     * @param array<string, string> $apiInfo
     */
    private function getClassCodeNode(string $fqn, array $apiInfo, string $role, string $name, string $type, string $member): CodeInlineNode
    {
        // Markdown has no modal to tell the class, so it writes both in full.
        $names = $member === '' ? ['fqn' => $fqn] : ['fqn' => $fqn, 'member' => $member];
        if ($apiInfo !== []) {
            if ($role === 'php-short') {
                $name = $apiInfo['short'] . $member;
            }
            $type = $apiInfo['type'];
            $modifiers = [];
            if ((bool)$apiInfo['final']) {
                $modifiers[] = 'final';
            }
            if ((bool)$apiInfo['abstract']) {
                $modifiers[] = 'abstract';
            }
            if ((bool)$apiInfo['readonly']) {
                $modifiers[] = 'readonly';
            }
            $modifiers[] = $apiInfo['type'];
            $flags = [];
            if ($apiInfo['internal']) {
                $flags[] = 'internal!';
            }
            if ($apiInfo['deprecated']) {
                $flags[] = 'deprecated!';
            }
            $apiInfo['signature'] = implode(' ', $modifiers) . ' ' . $apiInfo['short'];
            $apiInfo['flags'] = implode(' ', $flags);
            // The API ships summaries with HTML entities already applied. Decode
            // once so plain text is what travels through the data-* attribute.
            $apiInfo['summary'] = html_entity_decode($apiInfo['summary'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($member !== '') {
                $apiInfo['url'] .= $this->memberAnchor($type, $member);
            }
            $apiInfo = [...$apiInfo, ...$names];
            return new CodeInlineNode($name, $this->language($type, $member), '', $apiInfo);
        } elseif (str_starts_with($fqn, '\\TYPO3Fluid')) {
            $info = 'This PHP class or interface belongs to Fluid. ';
            return new CodeInlineNode(
                $name,
                $this->language($type, $member),
                $info,
                ['url' => 'https://docs.typo3.org/m/typo3/reference-coreapi/' . $this->typo3VersionService->getPreferredVersion() . '/en-us/ApiOverview/Fluid/Index.html', ...$names]
            );
        } elseif (str_starts_with($fqn, '\\Psr')) {
            return new CodeInlineNode(
                $name,
                $this->language($type, $member),
                'This PHP class or interface belongs to the PHP Standards Recommendations (PSR). ',
                ['url' => 'https://www.php-fig.org/psr/', ...$names]
            );
        } elseif (str_starts_with($fqn, '\\MyVendor') or str_starts_with($fqn, '\\Vendor')) {
            return new CodeInlineNode(
                $name,
                $this->language($type, $member),
                'PHP classes in this namespace are commonly used as examples. Replace with your own vendor and namespace on implementation. ',
                $names
            );
        }
        return new CodeInlineNode(
            $name,
            $this->language($type, $member),
            'This is a fully-qualified class or interface name,
            try searching for ' . $fqn . ' in the internet.',
            $names
        );
    }
    /**
     * What the popup names the code: the class, or the member of it that is
     * written. The member's kind is told from how it is written, and an enum
     * case from a constant only when the API knows the class is an enum.
     */
    private function language(string $type, string $member): string
    {
        return 'PHP ' . ($member === '' ? $type : $this->memberKind($type, $member));
    }

    private function memberKind(string $type, string $member): string
    {
        $name = substr($member, 2);
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*\s*\(/', $name) === 1) {
            return 'function';
        }
        if (str_starts_with($member, '->') || str_starts_with($name, '$')) {
            return 'property';
        }
        if (strtolower($name) === 'class') {
            return 'class name';
        }
        // A static property needs its "$", so a lowercase name is a function
        // written without its parentheses.
        if (preg_match('/^[a-z]/', $name) === 1) {
            return 'function';
        }
        return match ($type) {
            'enum' => 'enum case',
            'class or interface' => 'constant or enum case',
            default => 'constant',
        };
    }

    /**
     * The anchor of the member on its class's page of the API documentation,
     * or an empty string for a member that has none, like "::class".
     */
    private function memberAnchor(string $type, string $member): string
    {
        $prefix = match ($this->memberKind($type, $member)) {
            'function' => 'method_',
            'property' => 'property_',
            'enum case' => 'enumcase_',
            'constant' => 'constant_',
            default => '',
        };
        if ($prefix === '' || preg_match('/^\$?([A-Za-z_][A-Za-z0-9_]*)/', substr($member, 2), $matches) !== 1) {
            return '';
        }
        return '#' . $prefix . $matches[1];
    }

    /**
     * @param list<string> $matches
     */
    private function isClassName(string $name, array &$matches): bool
    {
        return (bool)preg_match(self::CLASS_NAME_PATTERN_REGEX, $name, $matches);
    }
}
