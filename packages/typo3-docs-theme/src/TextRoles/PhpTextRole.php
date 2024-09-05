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
        ];
    }

    public function processNode(DocumentParserContext $documentParserContext, string $role, string $content, string $rawContent): InlineNode
    {
        $fqn = [];
        $rawContent = trim($rawContent);
        if (str_contains($rawContent, '\\') && $this->isClassName($rawContent, $fqn)) {
            if (!str_starts_with($rawContent, '\\')) {
                $rawContent = '\\' . $rawContent;
            }
            $type = 'class or interface';
            $apiInfo = $this->typo3ApiService->getClassInfo($rawContent);
            $name = $rawContent;
            if ($role === 'php-short') {
                $name = $fqn[2] ?? $rawContent;
            }
            return $this->getClassCodeNode($rawContent, $apiInfo, $role, $name, $type);
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
    private function getClassCodeNode(string $fqn, array $apiInfo, string $role, string $name, string $type): CodeInlineNode
    {
        if ($apiInfo !== []) {
            if ($role === 'php-short') {
                $name = $apiInfo['short'];
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
            $infoArray = [];
            $infoArray[] = '<code>' . implode(' ', $modifiers) . ' ' . $apiInfo['short'] . '</code>';

            if ($role === 'php-short') {
                $infoArray[] =  '<code>' . $apiInfo['fqn'] . '</code>';
            }
            if ($apiInfo['internal']) {
                $infoArray[] = 'internal!';
            }
            if ($apiInfo['deprecated']) {
                $infoArray[] = 'deprecated!';
            }
            if ($apiInfo['summary']) {
                $infoArray[] = '<em>' . $apiInfo['summary'] . '</em>';
            }
            $info = implode('<br>', $infoArray);
            $info = str_replace('\\', '&#8203;\\', $info);
            return new CodeInlineNode($name, 'PHP ' . $type, $info, $apiInfo);
        } elseif (str_starts_with($fqn, '\\TYPO3Fluid')) {
            $info = 'This PHP class or interface belongs to Fluid. ';
            return new CodeInlineNode(
                $name,
                'PHP ' . $type,
                $info,
                ['url' => 'https://docs.typo3.org/m/typo3/reference-coreapi/' . $this->typo3VersionService->getPreferredVersion() . '/en-us/ApiOverview/Fluid/Index.html']
            );
        } elseif (str_starts_with($fqn, '\\Psr')) {
            return new CodeInlineNode(
                $name,
                'PHP ' . $type,
                'This PHP class or interface belongs to the PHP Standards Recommendations (PSR). ',
                ['url' => 'https://www.php-fig.org/psr/']
            );
        } elseif (str_starts_with($fqn, '\\MyVendor') or str_starts_with($fqn, '\\Vendor')) {
            return new CodeInlineNode(
                $name,
                'PHP ' . $type,
                'PHP classes in this namespace are commonly used as examples. Replace with your own vendor and namespace on implementation. ',
                []
            );
        }
        return new CodeInlineNode(
            $name,
            'PHP ' . $type,
            'This is a fully-qualified class or interface name,
            try searching for ' . $fqn . ' in the internet.',
            []
        );
    }
    /**
     * @param list<string> $matches
     */
    private function isClassName(string $name, array &$matches): bool
    {
        return (bool)preg_match(self::CLASS_NAME_PATTERN_REGEX, $name, $matches);
    }
}
