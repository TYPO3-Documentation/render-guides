<?php

namespace T3Docs\VersionHandling\Packagist;

final readonly class ComposerPackage
{
    public function __construct(
        private string $composerName,
        private string $composerCommand,
        private string $packagistStatus,
        private string $packagistLink = '',
        private string $description = '',
        private string $homepage = '',
        private string $documentation = '',
        private string $issues = '',
        private string $source = '',
        private bool $development = false,
        private string $type = '',
        private string $extensionKey = '',
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

    public function getType(): string
    {
        return $this->type;
    }

    public function getExtensionKey(): string
    {
        return $this->extensionKey;
    }
}
