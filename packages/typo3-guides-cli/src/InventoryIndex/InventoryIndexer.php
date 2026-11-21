<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\InventoryIndex;

use JsonException;

use function count;
use function file_get_contents;
use function is_array;
use function is_string;
use function json_decode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

/**
 * Reads a rendered manual's objects.inv.json into the SQLite index.
 *
 * The inventory is a JSON object of roles ("std:label", "std:doc", …), each
 * holding anchors, each a list of [project, version, target, title]. Only the
 * target matters to a permalink; the other three repeat what the repositories
 * table already holds.
 */
final class InventoryIndexer
{
    public function __construct(
        private readonly InventoryDatabase $database,
    ) {}

    /**
     * @return int the number of permalinks written
     *
     * @throws InvalidInventoryException
     */
    public function indexFile(Artifact $artifact, string $path): int
    {
        $json = @file_get_contents($path);
        if ($json === false) {
            throw new InvalidInventoryException(sprintf('The inventory file "%s" could not be read.', $path));
        }

        return $this->index($artifact, $json);
    }

    /**
     * @return int the number of permalinks written
     *
     * @throws InvalidInventoryException
     */
    public function index(Artifact $artifact, string $json): int
    {
        $permalinks = $this->read($json);

        $this->database->replaceInventory($artifact, $permalinks);

        return count($permalinks);
    }

    /**
     * @return list<Permalink>
     *
     * @throws InvalidInventoryException
     */
    private function read(string $json): array
    {
        try {
            $inventory = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidInventoryException('The inventory is not valid JSON: ' . $exception->getMessage(), 0, $exception);
        }

        if (!is_array($inventory)) {
            throw new InvalidInventoryException('The inventory must be a JSON object of roles.');
        }

        $permalinks = [];
        foreach ($inventory as $role => $entries) {
            if (!is_string($role) || !is_array($entries)) {
                throw new InvalidInventoryException('Every role of the inventory must be a JSON object of anchors.');
            }

            foreach ($entries as $anchor => $entry) {
                if (!is_string($anchor) || !is_array($entry) || !isset($entry[2]) || !is_string($entry[2])) {
                    throw new InvalidInventoryException(sprintf(
                        'The inventory entry "%s" in role "%s" is not a [project, version, target, title] list.',
                        (string) $anchor,
                        $role,
                    ));
                }

                $permalinks[] = new Permalink($role, $anchor, $entry[2]);
            }
        }

        return $permalinks;
    }
}
