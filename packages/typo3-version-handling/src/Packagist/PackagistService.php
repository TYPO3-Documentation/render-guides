<?php

namespace T3Docs\VersionHandling\Packagist;

class PackagistService
{
    /** @var array<string, ComposerPackage>  */
    private array $cache = [];
    private bool $timeoutOccurred = false;

    public function getComposerInfo(string $composerName): ComposerPackage
    {
        if (isset($this->cache[$composerName])) {
            return $this->cache[$composerName];
        }
        $url = sprintf("https://repo.packagist.org/p2/%s.json", $composerName);
        $packageResponse = $this->fetchPackageData($url);
        if (!is_string($packageResponse)) {
            $this->cache[$composerName] = new ComposerPackage($composerName, 'composer req ' . $composerName, 'not found');
            return $this->cache[$composerName];
        }

        // Decode JSON response
        $packageData = json_decode($packageResponse, true);
        if (!is_array($packageData)) {
            $this->cache[$composerName] = new ComposerPackage($composerName, 'composer req ' . $composerName, 'not found');
            return $this->cache[$composerName];
        }
        $packages = $packageData['packages'] ?? null;
        if (!is_array($packages)) {
            $this->cache[$composerName] = new ComposerPackage($composerName, 'composer req ' . $composerName, 'not found');
            return $this->cache[$composerName];
        }
        $composerVersions = $packages[$composerName] ?? null;
        if (!is_array($composerVersions) || !isset($composerVersions[0]) || !is_array($composerVersions[0])) {
            $this->cache[$composerName] = new ComposerPackage($composerName, 'composer req ' . $composerName, 'not found');
            return $this->cache[$composerName];
        }
        /** @var array<string, mixed> $packageVersionData */
        $packageVersionData = $composerVersions[0];

        $this->cache[$composerName] = $this->getComposerInfoFromJson(
            $packageVersionData,
            'found',
            'https://packagist.org/packages/' . $composerName
        );
        return $this->cache[$composerName];
    }

    /**
     * @param array<string, mixed> $composerJsonArray Content of the composer.json as array
     */
    public function getComposerInfoFromJson(array $composerJsonArray, string $packagistStatus = '', string $packagistUrl = ''): ComposerPackage
    {
        $composerName = $composerJsonArray['name'] ?? null;
        if (!is_string($composerName)) {
            throw new \Exception('composer.json does not contain key "name". Invalid composer.json');
        }
        $isDev = false;
        $keywords = $composerJsonArray['keywords'] ?? [];
        if (!is_array($keywords)) {
            $keywords = [];
        }
        if (in_array('testing', $keywords, true) || in_array('development', $keywords, true)) {
            $isDev = true;
        }

        $support = $composerJsonArray['support'] ?? [];
        $docsUrl = $this->getString(is_array($support) ? ($support['docs'] ?? '') : '');
        $issuesUrl = $this->getString(is_array($support) ? ($support['issues'] ?? '') : '');
        $sourceUrl = $this->getString(is_array($support) ? ($support['source'] ?? '') : '');
        $extensionKey = '';
        if (
            isset($composerJsonArray['extra'])
            && is_array($composerJsonArray['extra'])
            && isset($composerJsonArray['extra']['typo3/cms'])
            && is_array($composerJsonArray['extra']['typo3/cms'])
        ) {
            $extensionKey = $this->getString($composerJsonArray['extra']['typo3/cms']['extension-key'] ?? '');
        }

        $composerPackage = new ComposerPackage(
            $composerName,
            'composer req ' . ($isDev ? '--dev ' : '') . $composerName,
            $packagistStatus,
            $packagistUrl,
            $this->getString($composerJsonArray['description'] ?? ''),
            $this->getString($composerJsonArray['homepage'] ?? ''),
            $docsUrl,
            $issuesUrl,
            $sourceUrl,
            $isDev,
            $this->getString($composerJsonArray['type'] ?? ''),
            $extensionKey,
        );
        return $composerPackage;
    }

    private function getString(mixed $value, string $default = ''): string
    {
        if (is_scalar($value)) {
            return (string)$value;
        }
        return $default;
    }


    public function fetchPackageData(string $url): bool|string
    {
        if ($this->timeoutOccurred) {
            return false;
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $errorNumber = curl_errno($ch);

        $response = curl_exec($ch);
        curl_close($ch);

        if ($errorNumber == CURLE_OPERATION_TIMEOUTED) {
            $this->timeoutOccurred = true;
        }

        return $response;
    }
}
