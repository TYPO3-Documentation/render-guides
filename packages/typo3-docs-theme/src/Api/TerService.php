<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Api;

use function curl_close;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt;
use function is_array;
use function is_string;
use function json_decode;
use function rawurlencode;

/**
 * Whether an extension is published in the TYPO3 Extension Repository (TER).
 * Not every extension is: some are on Packagist only.
 */
final class TerService
{
    /** @var array<string, bool> */
    private array $cache = [];

    /** @var \Closure(string): (string|null)|null */
    private ?\Closure $fetcher = null;

    /**
     * Fetch with this instead of over the network: the integration tests read
     * recorded responses. The callable returns the body, or null where the
     * TER does not know the extension or did not answer.
     *
     * @param callable(string): (string|null) $fetcher
     */
    public function fetchWith(callable $fetcher): void
    {
        $this->fetcher = $fetcher(...);
    }

    /**
     * False also where the TER did not answer: the link is then left out,
     * which is all that depends on it.
     */
    public function isPublished(string $extensionKey): bool
    {
        if (!isset($this->cache[$extensionKey])) {
            $url = 'https://extensions.typo3.org/api/v1/extension/' . rawurlencode($extensionKey);
            $body = $this->fetcher !== null ? ($this->fetcher)($url) : $this->request($url);
            $data = is_string($body) ? json_decode($body, true) : null;
            $this->cache[$extensionKey] = is_array($data) && $data !== [];
        }

        return $this->cache[$extensionKey];
    }

    public function getUrl(string $extensionKey): string
    {
        return 'https://extensions.typo3.org/extension/' . rawurlencode($extensionKey);
    }

    private function request(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        return $status === 200 && is_string($response) ? $response : null;
    }
}
