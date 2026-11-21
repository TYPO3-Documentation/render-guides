<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\InventoryIndex;

use InvalidArgumentException;

use function rtrim;
use function sprintf;
use function trim;

/**
 * One deployed version of one manual in one language: the identity of a row in
 * the repositories table.
 *
 * The address is normalized to end in "/" because every other address of the
 * artifact hangs off it, "objects.inv.json" included.
 */
final class Artifact
{
    public readonly string $repository;

    public readonly string $url;

    public readonly string $version;

    public readonly string $language;

    public function __construct(string $repository, string $url, string $version, string $language)
    {
        foreach (['repository' => $repository, 'url' => $url, 'version' => $version, 'language' => $language] as $name => $value) {
            if (trim($value) === '') {
                throw new InvalidArgumentException(sprintf('The artifact %s must not be empty.', $name));
            }
        }

        $this->repository = $repository;
        $this->url = rtrim($url, '/') . '/';
        $this->version = $version;
        $this->language = $language;
    }
}
