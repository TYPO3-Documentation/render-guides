<?php

namespace T3Docs\Typo3DocsTheme\Packagist;

class ComposerPackage
{
    public function __construct(
        private readonly string $composerName,
        private readonly string $composerCommand,
        private readonly string $packagistStatus,
        private readonly string $packagistLink = '',
        private readonly string $description = '',
        private readonly string $homepage = '',
        private readonly string $documentation = '',
        private readonly string $issues = '',
        private readonly string $source = '',
        private readonly bool $development = false,
    ) {}

    public function getComposerName(): string
    {
        return $this->composerName;
    }

    public function getComposerCommand(): string
    {
        return $this->composerCommand;
    }

    public function getPackagistLink(): string
    {
        return $this->packagistLink;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getHomepage(): string
    {
        return $this->homepage;
    }

    public function getDocumentation(): string
    {
        return $this->documentation;
    }

    public function getIssues(): string
    {
        return $this->issues;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getPackagistStatus(): string
    {
        return $this->packagistStatus;
    }

    public function isDevelopment(): bool
    {
        return $this->development;
    }
}
