<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Repository\Exception;

final class FileException extends \RuntimeException
{
    public static function notFound(string $filePath): self
    {
        return new self(
            \sprintf(
                'File "%s" cannot be found!',
                $filePath,
            )
        );
    }

    public static function notReadable(string $filePath): self
    {
        return new self(
            \sprintf(
                'File "%s" cannot be read!',
                $filePath,
            )
        );
    }

    public static function notParsable(string $filePath): self
    {
        return new self(
            \sprintf(
                'File "%s" cannot be parsed!',
                $filePath,
            )
        );
    }
}
