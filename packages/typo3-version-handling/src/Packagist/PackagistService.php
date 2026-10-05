<?php

namespace T3Docs\VersionHandling\Packagist;

class PackagistService
{
    /**
     * The status of a package Packagist could not be asked about: it did not
     * answer, or answered with an error. Unlike "not found", this says nothing
     * about the package.
     */
    public const STATUS_UNREACHABLE = 'unreachable';

    /** How often a request is made before Packagist counts as unreachable. */
    private const ATTEMPTS = 2;

    /** The pause before the second attempt, in microseconds. */
    private const RETRY_DELAY = 1_000_000;

    /** @var array<string, ComposerPackage>  */
    private array $cache = [];

    /** @var \Closure(string): (string|false|null)|null */
    private ?\Closure $fetcher = null;

    /**
     * Fetch with this instead of over the network: the integration tests read
     * recorded responses, so that a render does not depend on Packagist being
     * reachable. The callable returns the body, null for a package Packagist
     * does not know, or false where Packagist did not answer.
     *
     * @param callable(string): (string|false|null) $fetcher
     */
    public function fetchWith(callable $fetcher): void
    {
        $this->fetcher = $fetcher(...);
    }

    /**
     * Set when Packagist timed out, or failed a request and its second
     * attempt. It is then not asked again in this run, so that a render does
     * not wait for it once per package.
     */
    private bool $unreachable = false;

    public function getComposerInfo(string $composerName, bool $dev = false): ComposerPackage
    {
        if (isset($this->cache[$composerName])) {
            return $this->cache[$composerName];
        }
        $url = sprintf("https://repo.packagist.org/p2/%s%s.json", $composerName, $dev ? '~dev' : '');
        $packageResponse = $this->fetchPackageData($url);
        if ($packageResponse === null) {
            $this->cache[$composerName] = new ComposerPackage($composerName, 'composer req ' . $composerName, 'not found');
            return $this->cache[$composerName];
        }

        // An answer that is no JSON is an error page, not a statement about
        // the package.
        $packageData = $packageResponse === false ? null : json_decode($packageResponse, true);
        if (!is_array($packageData)) {
            $this->cache[$composerName] = new ComposerPackage($composerName, 'composer req ' . $composerName, self::STATUS_UNREACHABLE);
            return $this->cache[$composerName];
        }
        $packages = $packageData['packages'] ?? null;
        if (!is_array($packages)) {
            $this->cache[$composerName] = new ComposerPackage($composerName, 'composer req ' . $composerName, 'not found');
            return $this->cache[$composerName];
        }
        $composerVersions = $packages[$composerName] ?? null;
        if (!is_array($composerVersions) || !isset($composerVersions[0]) || !is_array($composerVersions[0])) {
            // Try again with dev versions
            if (!$dev) {
                return $this->getComposerInfo($composerName, true);
            }
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


    /**
     * The body of Packagist's answer, null where Packagist does not know the
     * package, or false where it did not answer or answered with an error.
     * A failure is tried again once: a single dropped request is common, and
     * a render that fails on warnings would fail for it.
     */
    public function fetchPackageData(string $url): string|false|null
    {
        for ($attempt = 1; ; $attempt++) {
            if ($this->unreachable) {
                return false;
            }

            $response = $this->fetcher !== null ? ($this->fetcher)($url) : $this->request($url);
            if ($response !== false) {
                return $response;
            }

            if ($attempt >= self::ATTEMPTS) {
                $this->unreachable = true;
                return false;
            }

            if ($this->fetcher === null) {
                usleep(self::RETRY_DELAY);
            }
        }
    }

    private function request(string $url): string|false|null
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        $response = curl_exec($ch);
        $errorNumber = curl_errno($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($errorNumber === CURLE_OPERATION_TIMEOUTED) {
            $this->unreachable = true;
            return false;
        }

        if ($status === 404) {
            return null;
        }

        return $errorNumber === 0 && $status === 200 && is_string($response) ? $response : false;
    }
}
