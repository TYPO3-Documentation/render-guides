<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\InventoryIndex;

use PDO;
use PDOStatement;
use RuntimeException;

use function array_column;
use function array_keys;
use function count;
use function explode;
use function in_array;
use function is_string;
use function ksort;
use function parse_url;
use function sprintf;
use function str_starts_with;
use function trim;

/**
 * Read-only JSON views on the inventory database.
 *
 * The endpoints answer what the permalink resolver and the version switch ask:
 * which versions and languages a manual is deployed in, which manuals live
 * below a path segment, and which anchors a version defines. Nothing writes.
 *
 * Values are query parameters rather than path segments because a repository
 * may contain a slash ("t3docs/render-guides"), which a path would split.
 */
final class PermalinkApi
{
    private const ENDPOINTS = [
        '/versions?repository=<repository>',
        '/languages?repository=<repository>',
        '/permalinks?repository=<repository>&version=<version>',
        '/permalinks?repository=<repository>',
        '/manuals?directory=<directory>',
    ];

    public function __construct(
        private readonly PDO $connection,
    ) {}

    /**
     * @param array<string, mixed> $query
     *
     * @return array{status: int, body: array<string, mixed>}
     */
    public function handle(string $method, string $path, array $query = []): array
    {
        if ($method !== 'GET') {
            return $this->fail(405, sprintf('The method "%s" is not supported, use GET.', $method));
        }

        $resource = $this->resource($path);
        if ($resource === 'manuals') {
            return $this->manuals($this->value($query, 'directory'));
        }

        if (!in_array($resource, ['versions', 'languages', 'permalinks'], true)) {
            return $this->fail(404, sprintf('Unknown endpoint "%s".', $path), ['endpoints' => self::ENDPOINTS]);
        }

        $repository = $this->value($query, 'repository');
        if ($repository === null) {
            return $this->fail(400, 'The parameter "repository" is required.');
        }

        if (!$this->exists($repository)) {
            return $this->fail(404, sprintf('The repository "%s" is not indexed.', $repository));
        }

        return match ($resource) {
            'versions' => $this->versions($repository),
            'languages' => $this->languages($repository),
            default => $this->permalinks($repository, $this->value($query, 'version')),
        };
    }

    /**
     * @return array{status: int, body: array<string, mixed>}
     */
    private function versions(string $repository): array
    {
        return $this->ok([
            'repository' => $repository,
            'versions' => array_column($this->rows(
                'SELECT DISTINCT version FROM repositories WHERE repository = :repository ORDER BY version',
                ['repository' => $repository],
            ), 'version'),
        ]);
    }

    /**
     * @return array{status: int, body: array<string, mixed>}
     */
    private function languages(string $repository): array
    {
        return $this->ok([
            'repository' => $repository,
            'languages' => array_column($this->rows(
                'SELECT DISTINCT language FROM repositories WHERE repository = :repository ORDER BY language',
                ['repository' => $repository],
            ), 'language'),
        ]);
    }

    /**
     * The permalinks of one version of a repository, or of all its versions
     * when no version is given.
     *
     * @return array{status: int, body: array<string, mixed>}
     */
    private function permalinks(string $repository, ?string $version): array
    {
        if ($version !== null && !$this->exists($repository, $version)) {
            return $this->fail(
                404,
                sprintf('The repository "%s" has no version "%s".', $repository, $version),
            );
        }

        $sql = 'SELECT r.version AS version, r.language AS language, r.url AS url,
                       p.role AS role, p.anchor AS anchor, p.target AS target
                FROM permalinks p
                INNER JOIN repositories r ON r.uid = p.repositoryUid
                WHERE r.repository = :repository';
        $parameters = ['repository' => $repository];

        if ($version !== null) {
            $sql .= ' AND r.version = :version';
            $parameters['version'] = $version;
        }

        $permalinks = $this->rows($sql . ' ORDER BY r.version, r.language, p.role, p.anchor', $parameters);

        $body = ['repository' => $repository];
        if ($version !== null) {
            $body['version'] = $version;
        }

        return $this->ok($body + ['count' => count($permalinks), 'permalinks' => $permalinks]);
    }

    /**
     * All manuals whose deployed address lies below a path segment, for
     * example "m" or "m/typo3".
     *
     * @return array{status: int, body: array<string, mixed>}
     */
    private function manuals(?string $directory): array
    {
        if ($directory === null) {
            return $this->fail(400, 'The parameter "directory" is required.');
        }

        $directory = trim($directory, '/');
        if ($directory === '') {
            return $this->fail(400, 'The parameter "directory" must not be empty.');
        }

        $prefix = '/' . $directory . '/';
        $manuals = [];

        foreach ($this->rows('SELECT DISTINCT repository, url FROM repositories', []) as $row) {
            $path = parse_url((string) $row['url'], PHP_URL_PATH);
            if (is_string($path) && str_starts_with($path, $prefix)) {
                $manuals[(string) $row['repository']] = true;
            }
        }

        ksort($manuals);

        return $this->ok([
            'directory' => $directory,
            'count' => count($manuals),
            'manuals' => array_keys($manuals),
        ]);
    }

    private function exists(string $repository, ?string $version = null): bool
    {
        $sql = 'SELECT 1 FROM repositories WHERE repository = :repository';
        $parameters = ['repository' => $repository];

        if ($version !== null) {
            $sql .= ' AND version = :version';
            $parameters['version'] = $version;
        }

        return $this->statement($sql . ' LIMIT 1', $parameters)->fetchColumn() !== false;
    }

    /**
     * @param array<string, scalar> $parameters
     *
     * @return list<array<string, mixed>>
     */
    private function rows(string $sql, array $parameters): array
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = $this->statement($sql, $parameters)->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    /**
     * @param array<string, scalar> $parameters
     */
    private function statement(string $sql, array $parameters): PDOStatement
    {
        $statement = $this->connection->prepare($sql);
        if ($statement === false) {
            throw new RuntimeException('The inventory database rejected a statement.');
        }

        $statement->execute($parameters);

        return $statement;
    }

    private function resource(string $path): string
    {
        $segments = explode('/', trim($path, '/'));

        return $segments[count($segments) - 1];
    }

    /**
     * @param array<string, mixed> $query
     */
    private function value(array $query, string $name): ?string
    {
        $value = $query[$name] ?? null;
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return trim($value);
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return array{status: int, body: array<string, mixed>}
     */
    private function ok(array $body): array
    {
        return ['status' => 200, 'body' => $body];
    }

    /**
     * @param array<string, mixed> $extra
     *
     * @return array{status: int, body: array<string, mixed>}
     */
    private function fail(int $status, string $message, array $extra = []): array
    {
        return ['status' => $status, 'body' => ['error' => $message] + $extra];
    }
}
