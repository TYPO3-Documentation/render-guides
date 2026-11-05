<?php

declare(strict_types=1);

namespace T3Docs\Typo3Api\Documentation\Twig;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides `typo3_api_versions()`, which returns the versions of the API from
 * versions.json of the template. The nightly build reads the same file to
 * know which versions to render.
 */
final class VersionsExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('typo3_api_versions', $this->versions(...), ['needs_environment' => true]),
        ];
    }

    /** @return list<array{version: string, label: string, status: string}> */
    public function versions(Environment $twig): array
    {
        return json_decode(
            $twig->getLoader()->getSourceContext('versions.json')->getCode(),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    }
}
