<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Migration\Exception;

final class ProcessingException extends \RuntimeException
{
    public static function fileCannotBeCreated(string $path): self
    {
        return new self(
            \sprintf(
                'Could not create file "%s"',
                $path
            )
        );
    }

    public static function xmlCannotBeGenerated(): self
    {
        return new self('The XML file cannot be generated');
    }
}
