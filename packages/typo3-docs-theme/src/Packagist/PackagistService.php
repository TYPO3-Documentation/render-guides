<?php

namespace T3Docs\Typo3DocsTheme\Packagist;

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
        if (!isset($packageData['packages'][$composerName][0]) || !is_array($packageData['packages'][$composerName][0])) {
            $this->cache[$composerName] = new ComposerPackage($composerName, 'composer req ' . $composerName, 'not found');
            return $this->cache[$composerName];
        }
        $packageVersionData = $packageData['packages'][$composerName][0];
        $isDev = false;
        $keywords = $packageVersionData['keywords'] ?? [];
        if (in_array('testing', $keywords, true) || in_array('development', $keywords, true)) {
            $isDev = true;
        }
        $this->cache[$composerName] = new ComposerPackage(
            $composerName,
            'composer req ' . ($isDev ? '--dev ' : '') . $composerName,
            'found',
            'https://packagist.org/packages/' . $composerName,
            $packageVersionData['description'] ?? '',
            $packageVersionData['homepage'] ?? '',
            $packageVersionData['support']['docs'] ?? '',
            $packageVersionData['support']['issues'] ?? '',
            $packageVersionData['support']['source'] ?? '',
            $isDev
        );
        return $this->cache[$composerName];
    }


    public function fetchPackageData(string $url): bool|string
    {
        if ($this->timeoutOccurred) {
            return false;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
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
