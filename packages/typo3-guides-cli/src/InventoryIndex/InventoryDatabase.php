<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\InventoryIndex;

use PDO;
use PDOStatement;
use RuntimeException;
use Throwable;

use function dirname;
use function is_dir;
use function mkdir;
use function sprintf;

/**
 * The SQLite database that indexes every deployed manual.
 *
 * Two tables: one row per deployed artifact (a repository in one version and
 * one language) and one row per entry of its objects.inv.json. The entries
 * carry their role because the same anchor can name different targets in
 * different roles -- "std:label:columns" and "std:confval-menu:columns" both
 * exist in the TCA reference -- and dropping it would let one overwrite the
 * other.
 */
final class InventoryDatabase
{
    private const SCHEMA = [
        <<<'SQL'
            CREATE TABLE IF NOT EXISTS repositories (
                uid INTEGER PRIMARY KEY,
                repository TEXT NOT NULL,
                url TEXT NOT NULL,
                version TEXT NOT NULL,
                language TEXT NOT NULL,
                UNIQUE (repository, version, language)
            )
            SQL,
        <<<'SQL'
            CREATE TABLE IF NOT EXISTS permalinks (
                repositoryUid INTEGER NOT NULL REFERENCES repositories(uid) ON DELETE CASCADE,
                role TEXT NOT NULL,
                anchor TEXT NOT NULL,
                target TEXT NOT NULL,
                PRIMARY KEY (repositoryUid, role, anchor)
            )
            SQL,
        'CREATE INDEX IF NOT EXISTS permalinks_by_anchor ON permalinks (anchor)',
    ];

    public function __construct(
        private readonly PDO $connection,
    ) {
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // The database is written by a deployment and read by the permalink
        // resolver at the same time, so waiting is better than failing.
        $this->connection->exec('PRAGMA foreign_keys = ON');
        $this->connection->exec('PRAGMA busy_timeout = 5000');
        $this->createSchema();
    }

    /**
     * Opens the database, creating it and any missing directory on the way.
     */
    public static function open(string $path): self
    {
        if ($path === ':memory:') {
            return new self(new PDO('sqlite::memory:'));
        }

        $directory = dirname($path);
        if ($directory !== '' && !is_dir($directory) && !mkdir($directory, 0o777, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('The directory "%s" for the inventory database could not be created.', $directory));
        }

        return new self(new PDO('sqlite:' . $path));
    }

    public function createSchema(): void
    {
        foreach (self::SCHEMA as $statement) {
            $this->connection->exec($statement);
        }
    }

    /**
     * Writes one artifact and its permalinks, replacing what an earlier run
     * indexed for the same repository, version and language.
     *
     * @param list<Permalink> $permalinks
     *
     * @return int the uid of the repository row
     */
    public function replaceInventory(Artifact $artifact, array $permalinks): int
    {
        $this->connection->beginTransaction();

        try {
            $uid = $this->upsertRepository($artifact);

            $this->statement('DELETE FROM permalinks WHERE repositoryUid = :uid')
                ->execute(['uid' => $uid]);

            $insert = $this->statement(
                'INSERT INTO permalinks (repositoryUid, role, anchor, target)
                 VALUES (:uid, :role, :anchor, :target)',
            );
            foreach ($permalinks as $permalink) {
                $insert->execute([
                    'uid' => $uid,
                    'role' => $permalink->role,
                    'anchor' => $permalink->anchor,
                    'target' => $permalink->target,
                ]);
            }

            $this->connection->commit();
        } catch (Throwable $exception) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $exception;
        }

        return $uid;
    }

    private function upsertRepository(Artifact $artifact): int
    {
        $select = $this->statement(
            'SELECT uid FROM repositories
             WHERE repository = :repository AND version = :version AND language = :language',
        );
        $select->execute([
            'repository' => $artifact->repository,
            'version' => $artifact->version,
            'language' => $artifact->language,
        ]);
        $uid = $select->fetchColumn();

        if ($uid !== false) {
            // The address may move while the repository, version and language
            // stay the same: a preview deployment first, docs.typo3.org later.
            $this->statement('UPDATE repositories SET url = :url WHERE uid = :uid')
                ->execute(['url' => $artifact->url, 'uid' => $uid]);

            return (int) $uid;
        }

        $this->statement(
            'INSERT INTO repositories (repository, url, version, language)
             VALUES (:repository, :url, :version, :language)',
        )->execute([
            'repository' => $artifact->repository,
            'url' => $artifact->url,
            'version' => $artifact->version,
            'language' => $artifact->language,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    private function statement(string $sql): PDOStatement
    {
        $statement = $this->connection->prepare($sql);
        if ($statement === false) {
            throw new RuntimeException('The inventory database rejected a statement.');
        }

        return $statement;
    }
}
