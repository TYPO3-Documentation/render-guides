<?php

namespace T3Docs\Typo3DocsTheme\Api;

use Psr\Log\LoggerInterface;
use T3Docs\Typo3DocsTheme\Inventory\Typo3VersionService;

final class Typo3ApiService
{
    /** @var array<string, array<string, string>>|null  */
    private ?array $apiData = null;

    /** @var \Closure(string): (string|null)|null */
    private ?\Closure $fetcher = null;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Typo3VersionService $typo3VersionService,
    ) {}

    /**
     * Fetch with this instead of over the network: the integration tests read
     * recorded responses, so that a render does not depend on api.typo3.org
     * being reachable. The callable returns the body, or null for a failure.
     *
     * @param callable(string): (string|null) $fetcher
     */
    public function fetchWith(callable $fetcher): void
    {
        $this->fetcher = $fetcher(...);
    }

    /**
     * @return array<string, string>
     */
    public function getClassInfo(string $fqn): array
    {
        if ($this->apiData === null) {
            $version = $this->typo3VersionService->getPreferredVersion();
            $this->apiData = $this->loadApi('https://api.typo3.org/' . $version . '/api-info.json');
        }
        return $this->apiData[$fqn] ?? [];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function loadApi(string $url = 'https://api.typo3.org/main/api-info.json'): array
    {
        $jsonData = $this->fetchJsonData($url);
        if ($jsonData === null) {
            return [];
        }

        $apiData = $this->decodeJson($jsonData);
        if ($apiData === null) {
            return [];
        }

        if (!$this->validateApiData($apiData)) {
            return [];
        }

        return $this->processApiData($apiData);
    }

    /**
     * Fetch JSON data from the given URL.
     *
     * @return string|null
     */
    private function fetchJsonData(string $url): ?string
    {
        if ($this->fetcher !== null) {
            return ($this->fetcher)($url);
        }

        $ch = curl_init($url);

        if ($ch === false) {
            $this->logger->warning(
                sprintf(
                    'TYPO3 API could not be loaded from %s, curl is not available.',
                    $url
                )
            );
            return null;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $jsonData = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $this->logger->warning(
                sprintf(
                    'TYPO3 API could not be loaded from %s, cURL error: %s',
                    $url,
                    curl_error($ch)
                )
            );
            curl_close($ch);
            return null;
        }

        if ($httpStatus !== 200) {
            $this->logger->warning(
                sprintf(
                    'TYPO3 API could not be loaded from %s, HTTP request failed with status code %s',
                    $url,
                    $httpStatus
                )
            );
            curl_close($ch);
            return null;
        }

        curl_close($ch);
        if (!is_string($jsonData)) {
            $this->logger->warning(
                'TYPO3 API data is not a valid string.'
            );
            return null;
        }
        return $jsonData;
    }

    /**
     * Decode JSON data into an array.
     *
     * @param string $jsonData
     * @return array<string, array<string, string>>|null
     */
    private function decodeJson(string $jsonData): ?array
    {
        $apiData = json_decode($jsonData, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->warning(
                sprintf(
                    'Error decoding JSON: %s',
                    json_last_error_msg()
                )
            );
            return null;
        }

        if (!is_array($apiData)) {
            $this->logger->warning(
                'Decoded JSON data is not a valid array.'
            );
            return null;
        }

        /** @var array<string, array<string, string>> $apiData */
        return $apiData;
    }

    /**
     * Validate the structure of the API data.
     *
     * @param array<string, array<string, string>> $apiData
     * @return bool
     */
    private function validateApiData(array $apiData): bool
    {
        foreach ($apiData as $key => $class) {
            if (!is_string($key) || !is_array($class)) {
                $this->logger->warning(
                    'API data is malformed. Key should be a string and value should be an array.'
                );
                return false;
            }

            foreach ($class as $fqn => $info) {
                if (!is_string($fqn) || !is_scalar($info)) {
                    $this->logger->warning(
                        'API data is malformed. FQN should be a string and info should be scalar.'
                    );
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Process the API data to ensure correct types.
     *
     * @param array<string, array<string, string>> $apiData
     * @return array<string, array<string, string>>
     */
    private function processApiData(array $apiData): array
    {
        foreach ($apiData as $key => &$class) {
            foreach ($class as $fqn => $info) {
                $class[$fqn] = (string) $info;
            }
        }
        return $apiData;
    }

}
