<?php

namespace T3Docs\Typo3DocsTheme\Api;

use Psr\Log\LoggerInterface;
use T3Docs\Typo3DocsTheme\Inventory\Typo3VersionService;

final class Typo3ApiService
{
    /** The version of the format of classes/<Name>.json that this theme reads. */
    public const TYPE_SCHEMA_VERSION = 1;

    /**
     * A request that fails is tried once more after a second. The files of
     * api.typo3.org are briefly missing or incomplete while it is deployed,
     * and a render that fails on warnings would fail for it.
     */
    private const ATTEMPTS = 2;

    /** In microseconds. */
    private const RETRY_DELAY = 1_000_000;

    /** @var array<string, array<string, string>>|null  */
    private ?array $apiData = null;

    /** @var array<string, true>|null */
    private ?array $namespaces = null;

    /** @var \Closure(string): (string|false|null)|null */
    private ?\Closure $fetcher = null;

    /** @var array<string, array<string, mixed>|null> the API of a type, indexed by its name */
    private array $types = [];

    /** @var array<string, string> why the API of a type could not be loaded, indexed by its name */
    private array $typeFailures = [];

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Typo3VersionService $typo3VersionService,
    ) {}

    /**
     * Fetch with this instead of over the network: the integration tests read
     * recorded responses, so that a render does not depend on api.typo3.org
     * being reachable. The callable returns the body, null where the URL is
     * not found, or false where api.typo3.org did not answer.
     *
     * @param callable(string): (string|false|null) $fetcher
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
        return $this->apiData()[$fqn] ?? [];
    }

    /**
     * Whether a namespace holds a type the API documents, directly or further
     * down. The API lists types only, so a namespace is known when it is the
     * beginning of a type's fully qualified name.
     */
    public function isNamespace(string $fqn): bool
    {
        if ($this->namespaces === null) {
            $this->namespaces = [];
            foreach (array_keys($this->apiData()) as $type) {
                $namespace = $type;
                while (($separator = strrpos($namespace, '\\')) !== false && $separator > 0) {
                    $namespace = substr($namespace, 0, $separator);
                    $this->namespaces[$namespace] = true;
                }
            }
        }

        return isset($this->namespaces[$fqn]);
    }

    /** The page of a namespace in the API documentation. */
    public function getNamespaceUrl(string $fqn): string
    {
        return sprintf(
            'https://api.typo3.org/%s/namespaces/%s.html',
            $this->typo3VersionService->getPreferredVersion(),
            strtolower(str_replace('\\', '-', trim($fqn, '\\'))),
        );
    }

    /**
     * The API of a class, interface, trait, or enum with its members, as
     * api.typo3.org writes it to classes/<Vendor>/<Package>/<Name>.json, or
     * null if the API does not know the type.
     *
     * @return array<string, mixed>|null
     */
    public function getTypeApi(string $fqn): ?array
    {
        $fqn = trim($fqn, '\\');
        if (!array_key_exists($fqn, $this->types)) {
            $this->types[$fqn] = $this->loadType($fqn);
        }

        return $this->types[$fqn];
    }

    /**
     * Why getTypeApi() returned null although the API may know the type: it
     * did not answer, or not with the API of a type. Empty where the API does
     * not know the type.
     */
    public function getTypeFailure(string $fqn): string
    {
        return $this->typeFailures[trim($fqn, '\\')] ?? '';
    }

    /** The page of a class, interface, trait, or enum in the API documentation. */
    public function getTypeUrl(string $fqn): string
    {
        return sprintf(
            'https://api.typo3.org/%s/classes/%s.html',
            $this->typo3VersionService->getPreferredVersion(),
            str_replace('\\', '-', trim($fqn, '\\')),
        );
    }

    /** @return array<string, mixed>|null */
    private function loadType(string $fqn): ?array
    {
        $url = sprintf(
            'https://api.typo3.org/%s/classes/%s.json',
            $this->typo3VersionService->getPreferredVersion(),
            str_replace('\\', '/', $fqn),
        );

        // Each failure is reported by the directive that asked for the type,
        // which knows the document and the line.
        $jsonData = $this->fetchJsonData($url);
        if (!is_string($jsonData)) {
            if ($jsonData !== null) {
                $this->typeFailures[$fqn] = sprintf('%s could not be loaded, %s. Render again later.', $url, $jsonData->message);
            }
            return null;
        }

        $type = json_decode($jsonData, true);
        if (!is_array($type)) {
            $this->typeFailures[$fqn] = sprintf('%s is no JSON, %s. Render again later.', $url, json_last_error_msg());
            return null;
        }
        if (($type['schema'] ?? null) !== self::TYPE_SCHEMA_VERSION) {
            $this->typeFailures[$fqn] = sprintf(
                '%s is not in the format version %d that this theme reads.',
                $url,
                self::TYPE_SCHEMA_VERSION,
            );
            return null;
        }

        /** @var array<string, mixed> $type */
        return $type;
    }

    /** @return array<string, array<string, string>> */
    private function apiData(): array
    {
        if ($this->apiData === null) {
            $version = $this->typo3VersionService->getPreferredVersion();
            $this->apiData = $this->loadApi('https://api.typo3.org/' . $version . '/api-info.json');
        }
        return $this->apiData;
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function loadApi(string $url = 'https://api.typo3.org/main/api-info.json'): array
    {
        $jsonData = $this->fetchJsonData($url);
        if (!is_string($jsonData)) {
            // Not a warning: the author cannot act on it, and nothing breaks.
            // The PHP code in this render is only not linked to the API.
            $this->logger->notice(sprintf(
                'TYPO3 API could not be loaded from %s, %s. PHP code is not linked to the API in this render.',
                $url,
                $jsonData === null ? 'it was not found' : $jsonData->message,
            ));
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
     * The body of the answer, null where the URL is not found, or why it
     * could not be loaded.
     */
    private function fetchJsonData(string $url): string|FetchFailure|null
    {
        for ($attempt = 1; ; $attempt++) {
            $result = $this->fetcher !== null ? ($this->fetcher)($url) : $this->request($url);
            if ($result === false) {
                $result = new FetchFailure('api.typo3.org did not answer');
            }
            if (is_string($result) || $attempt >= self::ATTEMPTS) {
                return $result;
            }

            // A recording answers the same when it is asked again.
            if ($this->fetcher !== null) {
                return $result;
            }
            usleep(self::RETRY_DELAY);
        }
    }

    private function request(string $url): string|FetchFailure|null
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return new FetchFailure('curl is not available');
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $jsonData = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_errno($ch) !== 0 ? curl_error($ch) : '';
        curl_close($ch);

        if ($error !== '') {
            return new FetchFailure('cURL error: ' . $error);
        }
        if ($httpStatus === 404) {
            return null;
        }
        if ($httpStatus !== 200 || !is_string($jsonData)) {
            return new FetchFailure(sprintf('HTTP request failed with status code %s', $httpStatus));
        }

        return $jsonData;
    }

    /**
     * Decode JSON data into an array.
     *
     * Like a failed request, a broken api-info.json is a notice: it is
     * typically one that is still being written while api.typo3.org is
     * deployed.
     *
     * @param string $jsonData
     * @return array<string, array<string, string>>|null
     */
    private function decodeJson(string $jsonData): ?array
    {
        $apiData = json_decode($jsonData, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->notice(
                sprintf(
                    'Error decoding JSON: %s',
                    json_last_error_msg()
                )
            );
            return null;
        }

        if (!is_array($apiData)) {
            $this->logger->notice(
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
                $this->logger->notice(
                    'API data is malformed. Key should be a string and value should be an array.'
                );
                return false;
            }

            foreach ($class as $fqn => $info) {
                if (!is_string($fqn) || !is_scalar($info)) {
                    $this->logger->notice(
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
